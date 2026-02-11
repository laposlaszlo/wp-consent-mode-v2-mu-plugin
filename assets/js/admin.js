/**
 * Consent Mode V2 - Admin JavaScript
 * Handles admin interface functionality
 */

jQuery(document).ready(function($) {
    
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.cmv2-tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // WordPress Color Picker initialization
    if ($.fn.wpColorPicker) {
        $('.cmv2-color-picker').wpColorPicker();
    }
    
    // Color Presets and Translations from PHP
    const presets = window.CMV2_ADMIN && window.CMV2_ADMIN.presets ? window.CMV2_ADMIN.presets : {};
    const translations = window.CMV2_ADMIN && window.CMV2_ADMIN.translations ? window.CMV2_ADMIN.translations : {};
    
    // Language selector change handler
    $('#cmv2_default_language').on('change', function() {
        const lang = $(this).val();
        const texts = translations[lang];
        
        if (texts) {
            // Update text fields with translations
            $.each(texts, function(key, value) {
                const input = $('#cmv2_' + key);
                if (input.length && input.is('input[type="text"], textarea')) {
                    input.val(value);
                }
            });
        }
    });
    
    // Preset button click handler
    $('.preset-btn').on('click', function(e) {
        e.preventDefault();
        const presetName = $(this).data('preset');
        const preset = presets[presetName];
        
        if (preset) {
            // Apply colors
            $.each(preset, function(key, value) {
                const input = $('#cmv2_' + key);
                input.val(value);
                
                // Update color picker if initialized
                if (input.hasClass('wp-color-picker')) {
                    input.wpColorPicker('color', value);
                }
            });
            
            // Visual feedback
            $(this).addClass('button-primary').siblings().removeClass('button-primary');
            setTimeout(function() {
                $('.preset-btn').removeClass('button-primary');
            }, 2000);
        }
    });
    
});
