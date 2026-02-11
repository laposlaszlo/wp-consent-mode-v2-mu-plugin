/**
 * Consent Mode V2 - Frontend JavaScript (Refactored)
 * Modular architecture with separated concerns
 */

(function(){'use strict';
  
  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    // Configuration from PHP
    const CONFIG = window.CMV2_CONFIG || {};
    const LS_KEY = 'cmv2_state';
    const VERSION = CONFIG.version || '2025-10-08';
    const TTL_DAYS = CONFIG.ttl_days || 180;
    const USE_ZARAZ = CONFIG.use_zaraz || false;
    const ZARAZ_PURPOSE = CONFIG.zaraz_purpose_name || 'marketing';

    // ======================
    // Storage Manager Module
    // ======================
    const StorageManager = {
      read: function() {
        try {
          const cookies = document.cookie.split(';');
          for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].trim();
            if (cookie.startsWith(LS_KEY + '=')) {
              const value = cookie.substring(LS_KEY.length + 1);
              return JSON.parse(decodeURIComponent(value));
            }
          }
          return null;
        } catch(e) {
          console.warn('CMV2: Cookie read error', e);
          return null;
        }
      },
      
      write: function(state) {
        try {
          const maxAge = TTL_DAYS * 24 * 60 * 60; // seconds
          const value = encodeURIComponent(JSON.stringify(state));
          const secure = location.protocol === 'https:' ? '; Secure' : '';
          document.cookie = LS_KEY + '=' + value + '; path=/; max-age=' + maxAge + '; SameSite=Lax' + secure;
          return true;
        } catch(e) {
          console.error('CMV2: Cookie write error', e);
          return false;
        }
      },
      
      clear: function() {
        try {
          document.cookie = LS_KEY + '=; path=/; max-age=0';
          return true;
        } catch(e) {
          console.error('CMV2: Cookie clear error', e);
          return false;
        }
      },
      
      isValid: function(state) {
        if (!state || state.version !== VERSION) {
          return false;
        }
        const now = Math.floor(Date.now() / 1000);
        const ttlSeconds = TTL_DAYS * 24 * 60 * 60;
        return (now - (state.ts || 0)) < ttlSeconds;
      }
    };

    // ======================
    // Consent Manager Module
    // ======================
    const ConsentManager = {
      gtag: function() {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push(arguments);
      },
      
      update: function(choices) {
        const analytics = choices.analytics ? 'granted' : 'denied';
        const ads = choices.ads ? 'granted' : 'denied';

        // Google Consent Mode frissítése
        this.gtag('consent', 'update', {
          ad_storage: ads,
          analytics_storage: analytics,
          ad_user_data: ads,
          ad_personalization: ads
        });

        // Zaraz consent frissítése (ha engedélyezve van)
        if (USE_ZARAZ && window.zaraz && window.zaraz.consent) {
          try {
            const zarazConsent = {};
            zarazConsent[ZARAZ_PURPOSE] = choices.ads; // Marketing/Ads kapcsolva van?
            window.zaraz.consent.set(zarazConsent);
            
            // Debug log
            console.log('CMV2: Zaraz consent updated', zarazConsent);
          } catch(e) {
            console.error('CMV2: Zaraz consent error:', e.message || e);
            console.error('❌ A "' + ZARAZ_PURPOSE + '" Purpose ID nem található a Zaraz-ban!');
            console.error('� FIGYELEM: Az ID case-sensitive! "kujo" ≠ "kujO" ≠ "KUJO"');
            console.error('📍 Ellenőrizd: Cloudflare → Zaraz → Settings → Consent Management → Purposes');
            console.error('💡 MÁSOLD KI a Purpose ID mezőt és illeszd be pontosan úgy a WordPress adminba!');
            
            // Próbáljuk meg listázni az elérhető purpose-öket
            if (window.zaraz.consent.APIReady && window.zaraz.consent.purposes) {
              const availablePurposes = Object.keys(window.zaraz.consent.purposes);
              if (availablePurposes.length > 0) {
                console.log('✅ Elérhető Zaraz Purpose ID-k (pontos kis/nagybetűkkel):', availablePurposes);
              }
            }
          }
        }

        // GTM esemény
        window.dataLayer.push({
          event: 'cm_update',
          cmv2_version: VERSION,
          cmv2_analytics: analytics,
          cmv2_ads: ads,
          cmv2_zaraz: USE_ZARAZ
        });
      }
    };

    // ======================
    // UI Controller Module
    // ======================
    const UIController = {
      elements: {},
      
      init: function() {
        this.elements = {
          modal: document.getElementById('cmv2-modal'),
          btnOpen: document.getElementById('cmv2-open'),
          chkAnalytics: document.getElementById('cmv2-analytics'),
          chkAds: document.getElementById('cmv2-ads'),
          btnAcceptAllSimple: document.getElementById('cmv2-accept-all-simple'),
          btnAcceptAllDetailed: document.getElementById('cmv2-accept-all-detailed'),
          btnCustomize: document.getElementById('cmv2-customize'),
          btnSave: document.getElementById('cmv2-save'),
          simpleView: document.getElementById('cmv2-simple-view'),
          detailedView: document.getElementById('cmv2-detailed-view')
        };
        
        return this.elements.modal !== null;
      },
      
      showModal: function() {
        if (!this.elements.modal) return;
        this.elements.modal.classList.remove('cmv2-hidden');
        this.elements.modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
      },
      
      hideModal: function() {
        if (!this.elements.modal) return;
        
        // Remove focus from any element inside modal before hiding
        // This prevents aria-hidden + focused element conflict
        if (document.activeElement && this.elements.modal.contains(document.activeElement)) {
          document.activeElement.blur();
        }
        
        this.elements.modal.classList.add('cmv2-hidden');
        this.elements.modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
      },
      
      showSimpleView: function() {
        if (this.elements.simpleView) this.elements.simpleView.classList.remove('cmv2-hidden');
        if (this.elements.detailedView) this.elements.detailedView.classList.add('cmv2-hidden');
      },
      
      showDetailedView: function() {
        if (this.elements.simpleView) this.elements.simpleView.classList.add('cmv2-hidden');
        if (this.elements.detailedView) this.elements.detailedView.classList.remove('cmv2-hidden');
      },
      
      getChoices: function() {
        return {
          analytics: this.elements.chkAnalytics ? this.elements.chkAnalytics.checked : false,
          ads: this.elements.chkAds ? this.elements.chkAds.checked : false
        };
      },
      
      setChoices: function(choices) {
        if (this.elements.chkAnalytics) this.elements.chkAnalytics.checked = !!choices.analytics;
        if (this.elements.chkAds) this.elements.chkAds.checked = !!choices.ads;
      },
      
      acceptAll: function() {
        if (this.elements.chkAnalytics) this.elements.chkAnalytics.checked = true;
        if (this.elements.chkAds) this.elements.chkAds.checked = true;
      }
    };

    // ======================
    // Application Controller
    // ======================
    const App = {
      init: function() {
        // Default consent is already set by PHP in <head> before GTM loads
        // No need to set it again here to avoid duplicate cm_default events
        
        // Initialize UI
        if (!UIController.init()) {
          console.error('CMV2: Modal element not found');
          return;
        }
        
        // Load saved state
        this.loadState();
        
        // Bind event handlers
        this.bindEvents();
      },
      
      loadState: function() {
        const state = StorageManager.read();
        
        if (state && StorageManager.isValid(state)) {
          // Valid consent exists - apply but don't show modal
          UIController.setChoices(state.choices);
          UIController.hideModal();
        } else {
          // No valid consent - show banner
          UIController.showSimpleView();
          UIController.showModal();
        }
      },
      
      saveAndApply: function() {
        const choices = UIController.getChoices();
        const state = {
          version: VERSION,
          ts: Math.floor(Date.now() / 1000),
          choices: choices
        };
        
        if (StorageManager.write(state)) {
          ConsentManager.update(choices);
          UIController.hideModal();
        } else {
          console.error('CMV2: Failed to save consent');
        }
      },
      
      bindEvents: function() {
        const self = this;
        const el = UIController.elements;
        
        // Open button
        if (el.btnOpen) {
          el.btnOpen.addEventListener('click', function() {
            UIController.showSimpleView();
            UIController.showModal();
          });
        }
        
        // Accept all buttons (both views use same handler)
        const acceptAllHandler = function() {
          UIController.acceptAll();
          self.saveAndApply();
        };
        
        if (el.btnAcceptAllSimple) {
          el.btnAcceptAllSimple.addEventListener('click', acceptAllHandler);
        }
        
        if (el.btnAcceptAllDetailed) {
          el.btnAcceptAllDetailed.addEventListener('click', acceptAllHandler);
        }
        
        // Customize button
        if (el.btnCustomize) {
          el.btnCustomize.addEventListener('click', function() {
            UIController.showDetailedView();
          });
        }
        
        // Save button
        if (el.btnSave) {
          el.btnSave.addEventListener('click', function() {
            self.saveAndApply();
          });
        }
      }
    };

    // Start application
    try {
      App.init();
    } catch(e) {
      console.error('CMV2: Initialization failed', e);
    }

    // Public API
    window.CM = window.CM || {
      open: function() {
        UIController.showSimpleView();
        UIController.showModal();
      },
      reset: function() {
        StorageManager.clear();
        UIController.showSimpleView();
        UIController.showModal();
      },
      get: StorageManager.read
    };
  } // end init()
  
})();
