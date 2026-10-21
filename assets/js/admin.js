/**
 * Consent Mode V2 - Admin JavaScript
 * Section navigation, live preview, dirty tracking and AJAX save.
 * jQuery is only used for the WordPress color picker.
 */

(function () {
    'use strict';

    var app = document.getElementById('cmv2-app');
    if (!app) {
        return;
    }
    app.classList.add('cmv2-js');

    var data = window.CMV2_ADMIN || {};
    var presets = data.presets || {};
    var translations = data.translations || {};
    var i18n = data.i18n || {};

    var form = document.getElementById('cmv2-form');
    var stage = document.getElementById('cmv2-stage');
    var savebar = form.querySelector('.cmv2-savebar');
    var saveState = document.getElementById('cmv2-save-state');
    var saveBtn = document.getElementById('cmv2-save-btn');
    var toast = document.getElementById('cmv2-toast');

    var dirty = false;
    var toastTimer = null;

    function $(id) { return document.getElementById(id); }
    function field(key) { return $('cmv2_' + key); }
    function val(key) { var el = field(key); return el ? el.value : ''; }
    function checked(key) { var el = field(key); return !!(el && el.checked); }

    function fire(el, type) {
        el.dispatchEvent(new Event(type, { bubbles: true }));
    }

    // ---------------------------------------------------------------
    // Section navigation
    // ---------------------------------------------------------------
    var sections = app.querySelectorAll('.cmv2-section');
    var navItems = app.querySelectorAll('.cmv2-nav-item');
    var activeInForm = true;

    // The save bar is only useful on sections that belong to the settings form,
    // or when there is something unsaved to remind about
    function updateSavebar() {
        savebar.hidden = !activeInForm && !dirty;
    }

    function showSection(id) {
        var found = false;
        sections.forEach(function (s) {
            var on = s.getAttribute('data-section') === id;
            s.classList.toggle('is-active', on);
            if (on) { activeInForm = form.contains(s); }
            found = found || on;
        });
        if (!found && sections.length) {
            id = sections[0].getAttribute('data-section');
            sections[0].classList.add('is-active');
            activeInForm = form.contains(sections[0]);
        }
        updateSavebar();
        navItems.forEach(function (a) {
            var on = a.getAttribute('data-section') === id;
            a.classList.toggle('is-active', on);
            if (on) { a.setAttribute('aria-current', 'page'); } else { a.removeAttribute('aria-current'); }
        });
    }

    navItems.forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var id = a.getAttribute('data-section');
            showSection(id);
            if (history.replaceState) { history.replaceState(null, '', '#' + id); }
        });
    });
    showSection(window.location.hash.replace('#', '') || 'general');

    // ---------------------------------------------------------------
    // Live preview
    // ---------------------------------------------------------------
    var colorVars = {
        background_color: '--p-bg',
        text_color: '--p-text',
        link_color: '--p-link',
        border_color: '--p-border',
        primary_color: '--p-primary',
        primary_text_color: '--p-primary-text',
        secondary_color: '--p-secondary',
        secondary_text_color: '--p-secondary-text',
        backdrop_color: '--p-backdrop'
    };

    var textTargets = {
        title: 'cmv2p-title',
        description: 'cmv2p-desc',
        privacy_link_text: 'cmv2p-privacy',
        necessary_label: 'cmv2p-necessary',
        analytics_label: 'cmv2p-analytics',
        ads_label: 'cmv2p-ads',
        accept_all_text: ['cmv2p-accept', 'cmv2p-accept2'],
        reject_all_text: 'cmv2p-reject',
        customize_text: 'cmv2p-customize',
        save_text: 'cmv2p-save',
        open_button_text: 'cmv2p-open'
    };

    function setText(ids, text) {
        [].concat(ids).forEach(function (id) {
            var el = $(id);
            if (el) { el.textContent = text; }
        });
    }

    function updatePreview() {
        Object.keys(textTargets).forEach(function (key) {
            setText(textTargets[key], val(key));
        });

        Object.keys(colorVars).forEach(function (key) {
            var v = val(key).trim();
            var valid = v && (!window.CSS || !CSS.supports || CSS.supports('color', v));
            if (valid) {
                stage.style.setProperty(colorVars[key], v);
            }
        });

        var radius = parseInt(val('border_radius'), 10);
        stage.style.setProperty('--p-radius', (isNaN(radius) ? 12 : radius) + 'px');
        var out = $('cmv2-radius-out');
        if (out) { out.textContent = isNaN(radius) ? '' : radius; }

        var position = form.querySelector('input[name="cmv2_popup_position"]:checked');
        stage.setAttribute('data-position', position ? position.value : 'center');

        $('cmv2p-reject').style.display = checked('show_reject_button') ? '' : 'none';
        $('cmv2p-open').hidden = !checked('show_open_button');
    }

    app.querySelectorAll('.cmv2-preview-views button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var view = btn.getAttribute('data-view');
            app.querySelectorAll('.cmv2-preview-views button').forEach(function (b) {
                var on = b === btn;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            stage.querySelectorAll('.cmv2p-view').forEach(function (v) {
                v.hidden = v.getAttribute('data-view') !== view;
            });
        });
    });

    // ---------------------------------------------------------------
    // Dirty tracking
    // ---------------------------------------------------------------
    function setDirty(state) {
        dirty = state;
        savebar.classList.toggle('is-dirty', state);
        saveState.textContent = state ? i18n.dirty : i18n.clean;
        updateSavebar();
    }

    function onChange() {
        setDirty(true);
        updatePreview();
        updateDepends();
    }
    form.addEventListener('input', onChange);
    form.addEventListener('change', onChange);

    window.addEventListener('beforeunload', function (e) {
        if (dirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // ---------------------------------------------------------------
    // Conditional fields
    // ---------------------------------------------------------------
    function updateDepends() {
        app.querySelectorAll('[data-depends]').forEach(function (el) {
            var source = $(el.getAttribute('data-depends'));
            el.style.display = source && !source.checked ? 'none' : '';
        });
    }

    // ---------------------------------------------------------------
    // Color pickers (WordPress)
    // ---------------------------------------------------------------
    var applyingPreset = false;

    function clearActivePreset() {
        app.querySelectorAll('.cmv2-preset.is-active').forEach(function (b) { b.classList.remove('is-active'); });
    }

    if (window.jQuery && window.jQuery.fn.wpColorPicker) {
        window.jQuery('.cmv2-color-picker').wpColorPicker({
            change: function (e, ui) {
                var input = e.target;
                input.value = ui.color.toString();
                fire(input, 'input');
            },
            clear: function (e) {
                var input = window.jQuery(e.target).closest('.wp-picker-container').find('.cmv2-color-picker')[0];
                setTimeout(function () { if (input) { fire(input, 'input'); } }, 0);
            }
        });
    }

    // ---------------------------------------------------------------
    // Presets
    // ---------------------------------------------------------------
    app.querySelectorAll('.cmv2-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var preset = presets[btn.getAttribute('data-preset')];
            if (!preset) { return; }

            applyingPreset = true;
            Object.keys(preset).forEach(function (key) {
                var input = field(key);
                if (!input) { return; }
                input.value = preset[key];
                if (window.jQuery && window.jQuery(input).hasClass('wp-color-picker')) {
                    window.jQuery(input).wpColorPicker('color', preset[key]);
                }
            });
            applyingPreset = false;

            clearActivePreset();
            btn.classList.add('is-active');
            setDirty(true);
            updatePreview();
        });
    });

    form.addEventListener('input', function (e) {
        // A manual color edit leaves the selected preset
        if (!applyingPreset && e.target.classList && e.target.classList.contains('cmv2-color-picker')) {
            clearActivePreset();
        }
    });

    // ---------------------------------------------------------------
    // Language: load built-in texts
    // ---------------------------------------------------------------
    var lang = field('default_language');
    if (lang) {
        lang.addEventListener('change', function () {
            var texts = translations[lang.value];
            if (!texts) { return; }
            Object.keys(texts).forEach(function (key) {
                var input = field(key);
                if (input) {
                    input.value = texts[key];
                    fire(input, 'input');
                }
            });
        });
    }

    // ---------------------------------------------------------------
    // Toast + AJAX save
    // ---------------------------------------------------------------
    function showToast(message, kind) {
        toast.textContent = message;
        toast.className = 'cmv2-toast' + (kind ? ' is-' + kind : '');
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.hidden = true; }, 4500);
    }

    form.addEventListener('submit', function (e) {
        if (!window.fetch || !window.FormData) {
            return; // Fall back to a normal submit
        }
        e.preventDefault();

        var body = new FormData(form);
        body.append('cmv2_save_settings', '1');

        saveBtn.disabled = true;
        saveState.textContent = i18n.saving;

        fetch(window.location.href.split('#')[0], {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            redirect: 'follow'
        }).then(function (res) {
            if (!res.ok) { throw new Error('HTTP ' + res.status); }
            var code = new URL(res.url).searchParams.get('cmv2_notice');
            if (code !== 'success' && code !== 'success_with_gtm_warning') { throw new Error('Unexpected result'); }

            setDirty(false);
            if (code === 'success_with_gtm_warning') {
                var gtm = field('gtm_container_id');
                if (gtm) { gtm.value = ''; }
                showToast(i18n.savedGtm, 'warn');
            } else {
                showToast(i18n.saved);
            }
        }).catch(function () {
            setDirty(true);
            showToast(i18n.error, 'error');
        }).then(function () {
            saveBtn.disabled = false;
        });
    });

    // Import confirmation
    var importBtn = $('cmv2-import-btn');
    if (importBtn) {
        importBtn.addEventListener('click', function (e) {
            if (!window.confirm(i18n.confirmImport)) { e.preventDefault(); }
        });
    }

    updateDepends();
    updatePreview();
    setDirty(false);
})();
