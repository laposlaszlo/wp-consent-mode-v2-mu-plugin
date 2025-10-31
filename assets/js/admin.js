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
    
    // Color Presets
    const presets = {
        default: {
            primary_color: '#111111',
            primary_text_color: '#ffffff',
            secondary_color: '#ffffff',
            secondary_text_color: '#000000',
            background_color: '#ffffff',
            text_color: '#000000',
            border_color: '#d0d0d0',
            link_color: '#0066cc'
        },
        blue: {
            primary_color: '#0066cc',
            primary_text_color: '#ffffff',
            secondary_color: '#e6f2ff',
            secondary_text_color: '#0066cc',
            background_color: '#ffffff',
            text_color: '#2c3e50',
            border_color: '#b3d9ff',
            link_color: '#0052a3'
        },
        green: {
            primary_color: '#2ecc71',
            primary_text_color: '#ffffff',
            secondary_color: '#f0fff4',
            secondary_text_color: '#27ae60',
            background_color: '#ffffff',
            text_color: '#2c3e50',
            border_color: '#a8e6cf',
            link_color: '#27ae60'
        },
        purple: {
            primary_color: '#9b59b6',
            primary_text_color: '#ffffff',
            secondary_color: '#f4ecf7',
            secondary_text_color: '#8e44ad',
            background_color: '#ffffff',
            text_color: '#34495e',
            border_color: '#d7bde2',
            link_color: '#8e44ad'
        },
        dark: {
            primary_color: '#ffffff',
            primary_text_color: '#000000',
            secondary_color: '#333333',
            secondary_text_color: '#ffffff',
            background_color: '#1a1a1a',
            text_color: '#ffffff',
            border_color: '#444444',
            link_color: '#4a9eff'
        },
        orange: {
            primary_color: '#ff6b35',
            primary_text_color: '#ffffff',
            secondary_color: '#fff5f0',
            secondary_text_color: '#d35400',
            background_color: '#ffffff',
            text_color: '#2c3e50',
            border_color: '#ffccbc',
            link_color: '#e74c3c'
        }
    };
    
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
