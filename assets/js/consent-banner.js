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
    const LANG_KEY = 'cmv2_language';
    const VERSION = CONFIG.version || '2025-10-08';
    const TTL_DAYS = CONFIG.ttl_days || 180;
    const TRANSLATIONS = CONFIG.translations || {};
    const DEFAULT_LANGUAGE = CONFIG.default_language || 'hu';

    // ======================
    // Language Manager Module
    // ======================
    const LanguageManager = {
      currentLanguage: DEFAULT_LANGUAGE,
      
      init: function() {
        // Load language from localStorage or use default
        const saved = this.getStoredLanguage();
        this.currentLanguage = saved || DEFAULT_LANGUAGE;
        this.applyLanguage(this.currentLanguage);
        this.updateActiveButton();
      },
      
      getStoredLanguage: function() {
        try {
          return localStorage.getItem(LANG_KEY);
        } catch(e) {
          return null;
        }
      },
      
      setStoredLanguage: function(lang) {
        try {
          localStorage.setItem(LANG_KEY, lang);
          return true;
        } catch(e) {
          return false;
        }
      },
      
      changeLanguage: function(lang) {
        if (!TRANSLATIONS[lang]) {
          console.warn('CMV2: Unknown language', lang);
          return false;
        }
        this.currentLanguage = lang;
        this.setStoredLanguage(lang);
        this.applyLanguage(lang);
        this.updateActiveButton();
        return true;
      },
      
      applyLanguage: function(lang) {
        const texts = TRANSLATIONS[lang];
        if (!texts) return;
        
        // Update all elements with data-i18n attribute
        document.querySelectorAll('[data-i18n]').forEach(function(el) {
          const key = el.getAttribute('data-i18n');
          if (texts[key]) {
            el.textContent = texts[key];
          }
        });
      },
      
      updateActiveButton: function() {
        document.querySelectorAll('.cmv2-lang-btn').forEach(function(btn) {
          if (btn.getAttribute('data-lang') === LanguageManager.currentLanguage) {
            btn.classList.add('active');
          } else {
            btn.classList.remove('active');
          }
        });
      }
    };

    // ======================
    // Storage Manager Module
    // ======================
    const StorageManager = {
      read: function() {
        try {
          return JSON.parse(localStorage.getItem(LS_KEY));
        } catch(e) {
          console.warn('CMV2: Storage read error', e);
          return null;
        }
      },
      
      write: function(state) {
        try {
          localStorage.setItem(LS_KEY, JSON.stringify(state));
          return true;
        } catch(e) {
          console.error('CMV2: Storage write error (quota exceeded?)', e);
          return false;
        }
      },
      
      clear: function() {
        try {
          localStorage.removeItem(LS_KEY);
          return true;
        } catch(e) {
          console.error('CMV2: Storage clear error', e);
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

        this.gtag('consent', 'update', {
          ad_storage: ads,
          analytics_storage: analytics,
          ad_user_data: ads,
          ad_personalization: ads
        });

        window.dataLayer.push({
          event: 'cm_update',
          cmv2_version: VERSION,
          cmv2_analytics: analytics,
          cmv2_ads: ads
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
        
        // Initialize language
        LanguageManager.init();
        
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
          ConsentManager.update(state.choices);
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
        
        // Language buttons
        document.querySelectorAll('.cmv2-lang-btn').forEach(function(btn) {
          btn.addEventListener('click', function() {
            const lang = this.getAttribute('data-lang');
            LanguageManager.changeLanguage(lang);
          });
        });
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
