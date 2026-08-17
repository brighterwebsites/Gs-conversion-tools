/* global gtag, dataLayer */
/**
 * Quote form prefill.
 * Only loads when source=quiz or source=calculator is in the URL (enforced by PHP).
 * Reads URL params and pre-fills the Breakdance quote form.
 */
(function () {
    'use strict';

    // Only the parameters the quiz and calculator actually produce are carried
    // into the form. Anything else in the URL is ignored, so a crafted link
    // cannot stuff arbitrary text into a submitted quote or the email it sends.
    var ALLOWED_PARAMS = [
        'source', 'size', 'bays', 'install', 'estimated_price', 'base_price',
        'install_price', 'starting', 'horses', 'horse_size', 'surface',
        'climate', 'additions', 'anchors', 'roof', 'addon',
    ];

    function getParams() {
        var out = {};
        new URLSearchParams(window.location.search).forEach(function (v, k) {
            if (ALLOWED_PARAMS.indexOf(k) === -1) {
                return;
            }
            // Strip newlines so a value cannot forge extra lines in the summary,
            // and cap the length -- nothing legitimate here is long.
            out[k] = String(v).replace(/[\r\n]+/g, ' ').slice(0, 100);
        });
        return out;
    }

    // Breakdance names its inputs fields[...]. document.querySelector('form')
    // returns the theme's search form on most pages, so quiz answers were being
    // attached to search instead of the quote form. Find the real one.
    function findQuoteForm() {
        var forms = document.querySelectorAll('form');
        var i, f;

        // Preferred signal: Breakdance field naming.
        for (i = 0; i < forms.length; i++) {
            if (forms[i].querySelector('[name^="fields["]')) {
                return forms[i];
            }
        }
        // Fallback: any non-search form that collects contact details. Note there
        // is deliberately no "if there's only one form, use it" fallback -- on a
        // page whose single form is the theme's search box, that picked search.
        for (i = 0; i < forms.length; i++) {
            f = forms[i];
            if (f.getAttribute('role') === 'search') continue;
            if (f.querySelector('textarea, input[type="email"]')) {
                return f;
            }
        }
        return null;
    }

    var SIZE_KEYS = ['4x4', '5x4', '4x5', '5x5'];

    function bannerMessage(data) {
        // Only echo values we recognise. Anything else falls back to the generic
        // wording, so a crafted query string can never reach the banner text.
        var size = SIZE_KEYS.indexOf(String(data.size || '')) !== -1 ? String(data.size) : '';
        var bays = /^[1-9][0-9]?$/.test(String(data.bays || '')) ? String(data.bays) : '1';

        return size
            ? 'We\'ve pre-filled this form with your ' + size + ' ' + bays + '-bay configuration'
            : 'We\'ve pre-filled this form with your calculator estimate';
    }

    function showBanner(data, form) {
        var banner = document.createElement('div');
        banner.className = 'gs-prefill-banner';

        var icon = document.createElement('span');
        icon.className = 'gs-prefill-banner__icon';
        icon.textContent = '✅';

        var text = document.createElement('span');
        text.className = 'gs-prefill-banner__text';

        var strong = document.createElement('strong');
        strong.textContent = 'Based on Your Results';
        text.appendChild(strong);

        // Built as a text node, never innerHTML: this string carries URL values.
        text.appendChild(document.createTextNode(' — ' + bannerMessage(data)));

        banner.appendChild(icon);
        banner.appendChild(text);

        form.parentNode.insertBefore(banner, form);
    }

    function prefillSingleCheckbox(root, name, targetVal) {
        var cbs = root.querySelectorAll('input[name="' + name + '"]');
        if (!cbs.length) return false;
        var target = String(targetVal).toLowerCase().trim();
        // For model field, map 4x5 → 5x4 (form may not have 4x5 option)
        var isModel = name.indexOf('model') !== -1;
        var compare = (isModel && target === '4x5') ? '5x4' : target;
        var matched = false;
        cbs.forEach(function (cb) {
            var match = String(cb.value || '').toLowerCase().trim() === compare;
            cb.checked = match;
            if (match) matched = true;
        });
        return matched;
    }

    function prefillCheckboxArray(root, name, quizValue) {
        var cbs = root.querySelectorAll('input[name="' + name + '"]');
        if (!cbs.length) return;
        var target = String(quizValue).toLowerCase().trim();
        cbs.forEach(function (cb) {
            var lbl = (cb.nextElementSibling ? cb.nextElementSibling.textContent : '').toLowerCase();
            var val = String(cb.value || '').toLowerCase();
            if ((target === 'tack' || target === 'both') && (lbl.includes('tack') || val.includes('tack'))) {
                cb.checked = true;
            }
            if ((target === 'wash' || target === 'both') && (lbl.includes('wash') || val.includes('wash'))) {
                cb.checked = true;
            }
        });
    }

    function prefillCheckboxByValues(root, name, csvValues) {
        var cbs = root.querySelectorAll('input[name="' + name + '"]');
        if (!cbs.length) return;
        var vals = String(csvValues || '').split(',').map(function (v) { return v.trim().toLowerCase(); }).filter(Boolean);
        cbs.forEach(function (cb) {
            var v = String(cb.value || '').toLowerCase().trim();
            cb.checked = vals.some(function (t) { return t === v; });
        });
    }

    function prefillTextarea(root, sel, value, append) {
        var el = root.querySelector(sel);
        if (!el) return;
        if (append && el.value.trim()) {
            el.value = el.value.trim() + '\n\n' + value;
        } else {
            el.value = value;
        }
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function prefillInput(root, sel, value) {
        var el = root.querySelector(sel);
        if (!el) return;
        el.value = value;
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    var SIZE_LABELS = {
        '4x4': '4m × 4m Standard', '5x4': '5m × 4m Large',
        '4x5': '4m × 5m Large',    '5x5': '5m × 5m XLarge',
    };

    function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

    function buildSummary(d) {
        var fromCalc = d.source === 'calculator' || (d.estimated_price && !d.horses);
        if (fromCalc) {
            var label = SIZE_LABELS[d.size] || d.size || '';
            var bays  = d.bays || '1';
            var fmt   = function (n) { return Number(n || 0).toLocaleString('en-AU'); };
            var lines = [label + ' × ' + bays + ' bay' + (bays !== '1' ? 's' : '')];
            if (d.base_price) lines.push('Base: $' + fmt(d.base_price));
            if (d.install_price && Number(d.install_price) > 0) lines.push('Installation: $' + fmt(d.install_price));
            if (d.estimated_price) lines.push('Total: $' + fmt(d.estimated_price));
            return lines.join('\n');
        }
        var parts = ['=== FROM STABLE FINDER QUIZ ===', ''];
        if (d.size && d.bays) parts.push('Configuration: ' + d.size + ' × ' + d.bays + ' bay(s)');
        if (d.estimated_price) parts.push('Estimated Price: $' + Number(d.estimated_price).toLocaleString('en-AU'));
        if (d.install) parts.push('Installation: ' + (d.install === 'yes' ? 'Yes' : 'No'));
        parts.push('', 'Quiz Responses:');
        if (d.starting)   parts.push('• Starting Point: ' + cap(d.starting));
        if (d.horses)     parts.push('• Number of Horses: ' + d.horses);
        if (d.horse_size) parts.push('• Horse Size: ' + cap(d.horse_size));
        if (d.surface)    parts.push('• Ground Surface: ' + cap(d.surface));
        if (d.climate)    parts.push('• Climate Concern: ' + cap(d.climate));
        if (d.additions)  parts.push('• Future Additions: ' + cap(d.additions));
        if (d.anchors === 'yes')    parts.push('• Anchors: Recommended');
        if (d.roof === 'slanted')   parts.push('• Roof: Slanted (for airflow)');
        parts.push('', '===========================');
        return parts.join('\n');
    }

    function addHiddenFields(data, form) {
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = 'quiz_data'; h.value = JSON.stringify(data);
        form.appendChild(h);
        Object.keys(data).forEach(function (k) {
            var f = document.createElement('input');
            f.type = 'hidden'; f.name = 'quiz_' + k; f.value = data[k];
            form.appendChild(f);
        });
    }

    function prefill(data, form) {
        // Bays
        if (data.bays) prefillInput(form, 'input[name="fields[bays]"]', data.bays);

        // Model size (single checkbox)
        if (data.size) prefillSingleCheckbox(form, 'fields[model][]', data.size);

        // Structure type
        prefillSingleCheckbox(form, 'fields[type][]', data.starting === 'retrofit' ? 'Retrofit' : 'Stable');

        // Add-ons from quiz additions
        if (data.additions) prefillCheckboxArray(form, 'fields[addon][]', data.additions);

        // Add-ons from explicit addon param (CSV)
        if (data.addon) prefillCheckboxByValues(form, 'fields[addon][]', data.addon);

        // Message / notes
        prefillTextarea(form, 'textarea[name="fields[message]"]', buildSummary(data), true);

        // Hidden tracking fields -- onto the quote form, not whatever form is first.
        addHiddenFields(data, form);
    }

    function track(event, label, value) {
        if (typeof gtag !== 'undefined') {
            gtag('event', event, { event_category: 'Quiz', event_label: label, value: value });
        }
        if (typeof dataLayer !== 'undefined') {
            dataLayer.push({ event: event, eventCategory: 'Quiz', eventLabel: label, eventValue: value });
        }
    }

    function run() {
        var data = getParams();

        // Double-guard: require source=quiz or source=calculator
        // (PHP already gates loading, but this protects against inline use)
        if (data.source !== 'quiz' && data.source !== 'calculator') return;

        // No quote form on this page means there is nothing to prefill. Bail out
        // rather than decorating and stuffing hidden fields into the search form.
        var form = findQuoteForm();
        if (!form) return;

        showBanner(data, form);

        // Prefill after short delay to let page builder forms fully render
        setTimeout(function () {
            prefill(data, form);

            var label = (data.size && data.bays) ? data.size + ' ' + data.bays + '-bay' : 'quote prefilled';
            var price = parseInt(data.estimated_price, 10) || 0;

            track('quote_form_prefilled', label, price);
        }, 300);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }

})();
