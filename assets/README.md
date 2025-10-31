# Assets Directory

This directory contains all external CSS and JavaScript files for the Consent Mode V2 plugin (v2.0.0+).

## Structure

```
assets/
├── css/
│   ├── consent-banner.css  (2.1 KB) - Frontend banner styles
│   └── admin.css           (1.2 KB) - Admin interface styles
└── js/
    ├── consent-banner.js   (4.0 KB) - Frontend consent logic
    └── admin.js            (3.5 KB) - Admin preset functionality
```

## Frontend Assets

### `css/consent-banner.css`
- Modal and backdrop base styles
- Button and form element styling
- Responsive design (mobile breakpoints)
- Cookie icon button positioning
- Hover and transition effects

### `js/consent-banner.js`
- localStorage management
- Google Consent Mode V2 integration
- Modal show/hide logic
- Event listeners (buttons, ESC key, backdrop)
- Public API (`window.CM`)
- Config support (`CMV2_CONFIG` from PHP)

## Admin Assets

### `css/admin.css`
- Tab navigation styles
- Color preset card grid layout
- Status boxes (success, warning, info)
- Export/import button styling
- Form layout optimization

### `js/admin.js`
- Tab switching functionality
- WordPress Color Picker initialization
- Color preset application logic
- Preset card click handlers
- Dependencies: jQuery, wp-color-picker

## Loading

Assets are properly enqueued using WordPress hooks:

**Frontend:**
```php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('cmv2-banner-css', CMV2_PLUGIN_URL . '/assets/css/consent-banner.css', [], CMV2_VERSION);
    wp_enqueue_script('cmv2-banner-js', CMV2_PLUGIN_URL . '/assets/js/consent-banner.js', [], CMV2_VERSION, true);
});
```

**Admin:**
```php
add_action('admin_enqueue_scripts', function($hook) {
    if ('settings_page_cmv2-consent-settings' !== $hook) return;
    wp_enqueue_style('cmv2-admin-css', CMV2_PLUGIN_URL . '/assets/css/admin.css', [], CMV2_VERSION);
    wp_enqueue_script('cmv2-admin-js', CMV2_PLUGIN_URL . '/assets/js/admin.js', ['jquery', 'wp-color-picker'], CMV2_VERSION, true);
});
```

## Versioning

All assets use the plugin version (`CMV2_VERSION = '2.0.0'`) as cache-busting parameter:
```
consent-banner.css?ver=2.0.0
consent-banner.js?ver=2.0.0
```

This ensures users get the latest version after updates.

## Performance

- **Browser caching:** Assets are cacheable with proper versioning
- **Parallel loading:** CSS and JS load simultaneously
- **CDN ready:** Can be hosted on CDN for improved performance
- **Small file sizes:** Total 10.8 KB uncompressed

## Development

When modifying these files:

1. Test changes in a local WordPress installation
2. Verify browser console for errors
3. Check Network tab for successful loading (200 status)
4. Test both frontend and admin functionality
5. Update version number in `consent-mode-v2.php` if needed

## Production Optimization (Future)

Consider these optimizations for production:
- Minification (`.min.css`, `.min.js`)
- Gzip compression on server
- CDN distribution
- Source maps for debugging

---

*Last updated: 2025-10-08*
