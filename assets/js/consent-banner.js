/**
 * Consent Mode V2 - Frontend JavaScript
 * Handles user consent choices and Google Consent Mode integration
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

    // Helper functions
    function nowTs(){ 
      return Math.floor(Date.now()/1000); 
    }
    
    function days(n){ 
      return n*24*60*60; 
    }

    function readState(){ 
      try{ 
        return JSON.parse(localStorage.getItem(LS_KEY)); 
      } catch(e) { 
        return null; 
      } 
    }
  
  function writeState(obj){ 
    localStorage.setItem(LS_KEY, JSON.stringify(obj)); 
  }
  
  function clearState(){ 
    localStorage.removeItem(LS_KEY); 
  }

  // Google gtag helper
  window.dataLayer = window.dataLayer || [];
  function gtag(){ 
    window.dataLayer.push(arguments); 
  }

  /**
   * Set default consent (GDPR compliance)
   * Must be called BEFORE GTM loads
   */
  function setDefaultConsent() {
    // URL passthrough for cross-domain tracking
    gtag('set', 'url_passthrough', true);
    
    // Ads data redaction (GDPR compliance)
    gtag('set', 'ads_data_redaction', true);
    
    // Set default consent state
    // - analytics_storage: 'granted' (functional/necessary cookies enabled by default)
    // - ad_storage, ad_user_data, ad_personalization: 'denied' (marketing requires consent)
    gtag('consent', 'default', {
      ad_storage: 'denied',
      analytics_storage: 'denied', // Functional/necessary cookies enabled by default
      ad_user_data: 'denied',
      ad_personalization: 'denied',
      functionality_storage: 'granted',
      necessary_storage: 'granted',
      wait_for_update: 500, // Wait 500ms for user consent before firing tags
      region: ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE','GB','IS','LI','NO','CH'] // EU/EEA countries
    });
    
    // Push default consent event to dataLayer
    window.dataLayer.push({
      event: 'cm_default',
      consent_default: {
        ad_storage: 'denied',
        analytics_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        functionality_storage: 'granted',
        necessary_storage: 'granted'
      }
    });
  }

  /**
   * Apply consent choices to Google Consent Mode
   */
  function applyConsent(choices){
    const analytics = choices.analytics ? 'granted' : 'denied';
    const ads = choices.ads ? 'granted' : 'denied';

    gtag('consent', 'update', {
      'ad_storage': ads,
      'analytics_storage': analytics,
      'ad_user_data': ads,
      'ad_personalization': ads
    });

    window.dataLayer.push({
      event: 'cm_update',
      cmv2_version: VERSION,
      cmv2_analytics: analytics,
      cmv2_ads: ads
    });
  }

  // DOM elements - declared first
  let modal, btnOpen, chkAnalytics, chkAds, btnSave;
  let btnAcceptAllSimple, btnAcceptAllDetailed, btnCustomize;
  let simpleView, detailedView;

  /**
   * Show modal
   */
  function showModal(){ 
    if (!modal) return;
    modal.classList.remove('cmv2-hidden'); 
    modal.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';
  }
  
  /**
   * Hide modal
   */
  function hideModal(){ 
    if (!modal) return;
    modal.classList.add('cmv2-hidden'); 
    modal.setAttribute('aria-hidden','true'); 
    document.body.style.overflow = '';
  }

  /**
   * Show simple view (2 buttons)
   */
  function showSimpleView() {
    if (simpleView) simpleView.classList.remove('cmv2-hidden');
    if (detailedView) detailedView.classList.add('cmv2-hidden');
  }

  /**
   * Show detailed view (with toggles)
   */
  function showDetailedView() {
    if (simpleView) simpleView.classList.add('cmv2-hidden');
    if (detailedView) detailedView.classList.remove('cmv2-hidden');
  }

  /**
   * Initialize DOM elements and event handlers
   */
  function initDOM() {
    modal = document.getElementById('cmv2-modal');
    btnOpen = document.getElementById('cmv2-open');
    chkAnalytics = document.getElementById('cmv2-analytics');
    chkAds = document.getElementById('cmv2-ads');
    btnAcceptAllSimple = document.getElementById('cmv2-accept-all-simple');
    btnAcceptAllDetailed = document.getElementById('cmv2-accept-all-detailed');
    btnCustomize = document.getElementById('cmv2-customize');
    btnSave = document.getElementById('cmv2-save');
    simpleView = document.getElementById('cmv2-simple-view');
    detailedView = document.getElementById('cmv2-detailed-view');

    // Check if modal exists
    if (!modal) {
      console.warn('CMV2: Modal element not found');
      return false;
    }

    return true;
  }

  /**
   * Initialize on page load
   */
  (function initConsent(){ 
    // Set default consent BEFORE anything else
    setDefaultConsent();
    
    // Wait for DOM to be ready
    if (!initDOM()) {
      console.error('CMV2: Failed to initialize DOM elements');
      return;
    }

    try {
      const st = readState();
      if (st && st.version === VERSION && (nowTs() - (st.ts||0)) < days(TTL_DAYS)) {
        // Valid consent exists - apply it but DON'T show modal
        if (chkAnalytics) chkAnalytics.checked = !!st.choices.analytics;
        if (chkAds) chkAds.checked = !!st.choices.ads;
        applyConsent(st.choices);
        hideModal();
      } else {
        // No consent or expired - ALWAYS show banner on first visit
        showSimpleView();
        showModal();
      }
    } catch(e) { 
      console.error('CMV2 Error:', e);
      showSimpleView();
      showModal(); 
    }
  })();

  /**
   * Event handlers
   */
  
  // Open button (cookie icon)
  if (btnOpen) {
    btnOpen.addEventListener('click', function() {
      showSimpleView();
      showModal();
    });
  }
  
  // Accept all button (simple view)
  if (btnAcceptAllSimple) {
    btnAcceptAllSimple.addEventListener('click', function(){
      if (chkAnalytics) chkAnalytics.checked = true;
      if (chkAds) chkAds.checked = true;
      saveAndApply();
    });
  }

  // Customize button (simple view -> detailed view)
  if (btnCustomize) {
    btnCustomize.addEventListener('click', function(){
      showDetailedView();
    });
  }
  
  // Accept all button (detailed view)
  if (btnAcceptAllDetailed) {
    btnAcceptAllDetailed.addEventListener('click', function(){
      if (chkAnalytics) chkAnalytics.checked = true;
      if (chkAds) chkAds.checked = true;
      saveAndApply();
    });
  }
  
  // Save button (detailed view)
  if (btnSave) {
    btnSave.addEventListener('click', saveAndApply);
  }
  
  // Backdrop click - DISABLED (user must make a choice)
  // if (modal) {
  //   const backdrop = modal.querySelector('.cmv2-backdrop');
  //   if (backdrop) {
  //     backdrop.addEventListener('click', function(e){
  //       if (e.target === this) {
  //         hideModal();
  //       }
  //     });
  //   }
  // }
  
  // ESC key - DISABLED (user must make a choice)
  // document.addEventListener('keydown', function(e){
  //   if (e.key === 'Escape' && modal && !modal.classList.contains('cmv2-hidden')) {
  //     hideModal();
  //   }
  // });

  /**
   * Save consent and apply
   */
  function saveAndApply(){
    const state = {
      version: VERSION,
      ts: nowTs(),
      choices: {
        analytics: chkAnalytics ? !!chkAnalytics.checked : false,
        ads: chkAds ? !!chkAds.checked : false
      }
    };
    writeState(state);
    applyConsent(state.choices);
    hideModal();
  }

  /**
   * Public API
   */
  window.CM = window.CM || {
    open: function() {
      showSimpleView();
      showModal();
    },
    reset: function(){ 
      clearState();
      showSimpleView();
      showModal(); 
    },
    get: readState
  };
  
  } // end init()
  
})();
