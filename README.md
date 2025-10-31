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
├── consent-mode-v2.php          # Main plugin file (530 lines)
├── assets/                      # External assets (v2.0.0+)
│   ├── css/
│   │   ├── consent-banner.css   # Frontend banner styles
│   │   └── admin.css            # Admin interface styles
│   └── js/
│       ├── consent-banner.js    # Frontend consent logic
│       └── admin.js             # Admin preset logic
├── README.md                    # This file
├── REFAKTORING.md              # v2.0.0 refactoring docs (Hungarian)
├── GTM-snippets.txt            # GTM configuration examples
└── [Other documentation files in Hungarian]
```

---
