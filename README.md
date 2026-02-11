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
- ✅ localStorage persistence (180-day TTL)
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
