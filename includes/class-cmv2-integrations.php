<?php
/**
 * CMV2 Integrations Class
 * Third-party compatibility fixes
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMV2_Integrations
{
    public static function init()
    {
        add_action('wp_footer', [__CLASS__, 'render_gtmkit_remove_from_cart_fix'], 1);
    }

    /**
     * Fix GTMKIT remove_from_cart ecommerce value bug.
     *
     * Older GTMKIT versions divide item price by 100 when computing ecommerce.value
     * for remove_from_cart (e.g. 22 500 HUF → 225). The push happens entirely in
     * GTMKIT's JS, so the correction must also be client-side.
     *
     * Strategy: after window.load (when GTM container is guaranteed to have loaded
     * and replaced dataLayer.push), we wrap push once more. Any remove_from_cart
     * event whose value is exactly price×qty/100 gets corrected to price×qty before
     * GTM processes it.
     *
     * Also adds currency to events that are missing it so Meta pixel value is correct.
     * Can be disabled with the `cmv2_enable_gtmkit_fix` filter.
     */
    public static function render_gtmkit_remove_from_cart_fix()
    {
        if (!function_exists('WC') || !apply_filters('cmv2_enable_gtmkit_fix', true)) {
            return;
        }
        ?>
        <script>
        /* CMV2: GTMKIT remove_from_cart value fix */
        window.addEventListener('load', function () {
            var dl = window.dataLayer;
            if (!dl || typeof dl.push !== 'function') return;

            var _gtmPush = dl.push;

            dl.push = function () {
                var args = Array.prototype.slice.call(arguments);

                for (var i = 0; i < args.length; i++) {
                    var entry = args[i];

                    if (
                        entry &&
                        entry.event === 'remove_from_cart' &&
                        entry.ecommerce &&
                        typeof entry.ecommerce.value === 'number' &&
                        Array.isArray(entry.ecommerce.items) &&
                        entry.ecommerce.items.length > 0
                    ) {
                        var price = parseFloat(entry.ecommerce.items[0].price) || 0;
                        var qty   = parseInt(entry.ecommerce.items[0].quantity, 10) || 1;
                        var correct = price * qty;

                        /* Detect ÷100 bug: current value × 100 ≈ expected value */
                        if (correct > 0 && Math.round(entry.ecommerce.value * 100) === Math.round(correct)) {
                            entry.ecommerce.value = correct;
                        }

                        /* Ensure currency is present (some old GTMKIT versions omit it) */
                        if (!entry.ecommerce.currency && window.gtmkit_data && window.gtmkit_data.wc) {
                            entry.ecommerce.currency = window.gtmkit_data.wc.currency;
                        }
                    }
                }

                return _gtmPush.apply(dl, args);
            };
        });
        </script>
        <?php
    }
}
