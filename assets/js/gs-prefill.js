/* global gtag, dataLayer */
/**
 * Quote form prefill.
 * Only loads when source=quiz or source=calculator is in the URL (enforced by PHP).
 * Reads URL params and pre-fills the Breakdance quote form.
 */
(function () {
    'use strict';

    function getParams() {
        var out = {};
        new URLSearchParams(window.location.search).forEach(function (v, k) {
            out[k] = v;
        });
        return out;
    }

    function showBanner(data, form) {
        var banner = document.createElement('div');
        banner.className = 'gs-prefill-banner';
        var msg = data.size
            ? 'We\'ve pre-filled this form with your ' + data.size + ' ' + (data.bays || '1') + '-bay configuration'
            : 'We\'ve pre-filled this form with your calculator estimate';

        banner.innerHTML = '<span class="gs-prefill-banner__icon">✅</span>'
            + '<span class="gs-prefill-banner__text"><strong>Based on Your Results</strong> — ' + msg + '</span>';

        form.parentNode.insertBefore(banner, form);
    }

    function prefillSingleCheckbox(name, targetVal) {
        var cbs = document.querySelectorAll('input[name="' + name + '"]');
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

    function prefillCheckboxArray(name, quizValue) {
        var cbs = document.querySelectorAll('input[name="' + name + '"]');
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

    function prefillCheckboxByValues(name, csvValues) {
        var cbs = document.querySelectorAll('input[name="' + name + '"]');
        if (!cbs.length) return;
        var vals = String(csvValues || '').split(',').map(function (v) { return v.trim().toLowerCase(); }).filter(Boolean);
        cbs.forEach(function (cb) {
            var v = String(cb.value || '').toLowerCase().trim();
            cb.checked = vals.some(function (t) { return t === v; });
        });
    }

    function prefillTextarea(sel, value, append) {
        var el = document.querySelector(sel);
        if (!el) return;
        if (append && el.value.trim()) {
            el.value = el.value.trim() + '\n\n' + value;
        } else {
            el.value = value;
        }
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function prefillInput(sel, value) {
        var el = document.querySelector(sel);
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

    function prefill(data) {
        // Bays
        if (data.bays) prefillInput('input[name="fields[bays]"]', data.bays);

        // Model size (single checkbox)
        if (data.size) prefillSingleCheckbox('fields[model][]', data.size);

        // Structure type
        prefillSingleCheckbox('fields[type][]', data.starting === 'retrofit' ? 'Retrofit' : 'Stable');

        // Add-ons from quiz additions
        if (data.additions) prefillCheckboxArray('fields[addon][]', data.additions);

        // Add-ons from explicit addon param (CSV)
        if (data.addon) prefillCheckboxByValues('fields[addon][]', data.addon);

        // Message / notes
        prefillTextarea('textarea[name="fields[message]"]', buildSummary(data), true);

        // Hidden tracking fields
        var form = document.querySelector('form');
        if (form) addHiddenFields(data, form);
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

        var form = document.querySelector('form');
        if (form) showBanner(data, form);

        // Prefill after short delay to let page builder forms fully render
        setTimeout(function () {
            prefill(data);

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
