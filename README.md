# 🍪 Consent Mode V2 – WordPress Plugin

> Minimal, lightweight, and fully compliant Google Consent Mode V2 implementation for WordPress. Complete admin interface with color customization, text localization, and export/import capabilities.

**Version:** 2.0.0 (Refactored with external assets)  
**Author:** Custom Development  
**License:** GPL v2 or later  
**Requires:** WordPress 5.0+, PHP 7.4+

---

## ✨ Key Features

### 🎨 Full Admin Interface
- **3 tabs:** Texts, Colors, Advanced Settings
- **11 customizable text fields** (multilingual ready)
- **9 color settings** with WordPress Color Picker
- **6 preset color schemes** (Light, Dark, Blue, Green, Elegant, Minimalist)
- **Export/Import** JSON configuration

### � Google Consent Mode V2 Compliant
- ✅ Default consent signals in `<head>` (before GTM)
- ✅ Update consent signals after user interaction
- ✅ Cookie-based persistence (180-day TTL)
- ✅ GTM dataLayer events (`cm_update`)
- ✅ GDPR compliant

### 🚀 Performance Optimized (v2.0.0)
- ✅ **External CSS/JS files** (cacheable)
- ✅ **37% code reduction** (845 → 530 lines)
- ✅ **Faster load times** (~44% improvement)
- ✅ WordPress best practices (proper enqueuing)

### 📱 Modern UX
- Accessible (ARIA labels, keyboard navigation)
- Responsive (mobile-optimized)
- Smooth animations and transitions
- Cookie icon button for reopening preferences

---

## 📁 File Structure

```
consent-mode-v2-mu-plugin/
├── consent-mode-v2.php          # Main plugin file
├── composer.json                # Composer dependencies
├── composer.lock                # Locked dependencies versions
├── vendor/                      # Composer packages (Plugin Update Checker)
├── includes/                    # PHP classes
│   ├── class-cmv2-settings.php # Admin settings interface
│   └── class-cmv2-frontend.php # Frontend rendering & assets
├── assets/                      # External frontend assets
│   ├── css/
│   │   ├── consent-banner.css  # Frontend banner styles
│   │   └── admin.css           # Admin interface styles
│   └── js/
│       ├── consent-banner.js   # Frontend consent logic
│       └── admin.js            # Admin preset logic
├── README.md                    # Documentation (English)
├── RELEASE-GUIDE.md            # Release & update guide
└── REFACTOR-SUMMARY.md         # Refactoring docs (Hungarian)
```

---

## 📋 Changelog

### [2.8.0] - 2026-04-14

**Fixed:**
- GTM double-loading (duplicate `gtm.js` in dataLayer) caused by theme or other plugins injecting the same GTM container independently. The plugin's GTM snippet now includes a `_cmv2_gtm_loaded_{ID}` guard flag that prevents re-injection of the same container ID
- Passed `gtm_container_id` to the frontend JS config (`CMV2_CONFIG`) so the JS layer is aware of the configured container

**Added:**
- Runtime GTM duplication detector in JS (`App.detectGtmDuplicate`): counts `gtm.js` events in the dataLayer after DOMContentLoaded; if more than one is found, logs a `console.warn` with the container ID and count, and pushes a `cmv2_gtm_duplicate` event to the dataLayer for GTM-side monitoring
- Admin warning panel in the Advanced tab when a GTM Container ID is configured, listing the most common external sources of GTM duplication (theme `header.php`, MonsterInsights, Site Kit, WooCommerce)

### [2.7.1] - 2026-03-31

**Fixed:**
- After first-time consent grant with analytics enabled, manually push `page_view` and `session_start` to the dataLayer so GTM/GA4 can register the session (GTM has already loaded in `denied` state by the time the user clicks Accept; the automatic GA4 page_view was therefore missed)
- Returning visitors are unaffected: PHP already emits the consent `update` in `<head>` before GTM loads, so GA4 fires automatically

### [2.7.0] - 2026-03-31

**Added:**
- **Reject All button** in Simple View — one-click denial is now equally visible to Accept All (CNIL/APD compliance)
- **EEA-only banner** (`eea_only_banner` option): banner and consent JS are skipped for non-EEA visitors when a country header is available (Cloudflare `CF-IPCountry` / `X-Country-Code`); unknown country always shows banner as safe fallback
- **Custom region list** (`custom_regions` option): configurable comma/newline-separated ISO-3166-1 country code list for GCM `region` parameter, replacing the hard-coded EEA array
- **Google Ads modeling** (`use_google_ads` option): `url_passthrough` is now opt-in only; `ads_data_redaction` remains always-on
- **Configurable `wait_for_update`** (`wait_for_update_ms` option, 100–2000 ms): controls how long GTM waits for consent update from new visitors (default: 500 ms; returning visitors always use 0)

### [2.6.9] - 2026-03-31

**Fixed:**
- Consent Mode default always emits `denied` — this is the correct GCM v2 baseline for new visitors
- Returning visitors now receive an immediate `gtag('consent', 'update', ...)` in `<head>` before GTM loads, so tags fire correctly on every page reload
- JS `App.loadState()` now calls `ConsentManager.update()` on every page load for returning visitors, ensuring the `cm_update` dataLayer event is always dispatched

### [2.4.0] - 2025-02-11

**Added:**
- Automatic plugin updates from GitHub releases
- Popup position selector (center, bottom-left, bottom-right)
- Comprehensive release guide (RELEASE-GUIDE.md)
- Composer integration with Plugin Update Checker library

**Fixed:**
- Removed duplicate cm_default event in JavaScript
- Fixed consent event timing issues
- Removed OLD backup files

**Changed:**
- Updated plugin headers with proper metadata
- Enhanced README with installation and update instructions
- Improved code organization and documentation

### [2.3.0] - 2025-02-10
- Modular JavaScript architecture (refactored)
- Improved consent flow

### [2.0.0] - 2025-02-08
- External CSS/JS files (cacheable)
- Admin interface with color picker
- Export/Import settings
- 6 color presets
- 37% code reduction
- 44% faster load times

---

**Made with ❤️ for GDPR compliance and user privacy**
