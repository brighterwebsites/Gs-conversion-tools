/* global GS_PRICING_CONFIG, gtag, dataLayer */
(function () {
    'use strict';

    document.querySelectorAll('.gs-calc').forEach(function (wrap) {
        var sizeEl    = wrap.querySelector('#gs-calc-size');
        var baysEl    = wrap.querySelector('#gs-calc-bays');
        var installEl = wrap.querySelector('#gs-calc-install');
        var btn       = wrap.querySelector('#gs-calc-btn');
        var result    = wrap.querySelector('#gs-calc-result');
        var totalEl   = wrap.querySelector('#gs-calc-total');
        var breakdownEl = wrap.querySelector('#gs-calc-breakdown');
        var quoteLink = wrap.querySelector('#gs-calc-quote-link');

        if (!btn) return;

        var pricing = (window.GS_PRICING_CONFIG && window.GS_PRICING_CONFIG.base) || {
            '4x4': { first: 4500, extra: 4000, install: 550, label: '4m × 4m Standard' },
            '5x4': { first: 6100, extra: 5900, install: 700, label: '5m × 4m Large' },
            '4x5': { first: 6100, extra: 5900, install: 700, label: '4m × 5m Large' },
            '5x5': { first: 7800, extra: 6200, install: 850, label: '5m × 5m XLarge' },
        };

        // Apply shortcode defaults
        var defSize = wrap.dataset.defaultSize;
        var defBays = wrap.dataset.defaultBays;
        if (defSize && sizeEl.querySelector('option[value="' + defSize + '"]')) {
            sizeEl.value = defSize;
        }
        if (defBays && baysEl.querySelector('option[value="' + defBays + '"]')) {
            baysEl.value = defBays;
        }

        var lastTotal = null;

        function fmt(n) {
            return '$' + n.toLocaleString('en-AU');
        }

        function buildQuoteUrl() {
            var base = (wrap.dataset.quoteUrl || '/stable-quote/').replace(/\/?$/, '');
            var p = new URLSearchParams({
                size:   sizeEl.value,
                bays:   baysEl.value,
                source: 'calculator',
            });
            if (lastTotal !== null) {
                p.set('estimated_price', String(lastTotal));
                p.set('install', installEl.value);
            }
            return base + '?' + p.toString();
        }

        function updateQuoteLink() {
            if (quoteLink) quoteLink.href = buildQuoteUrl();
        }

        updateQuoteLink();
        [sizeEl, baysEl, installEl].forEach(function (el) {
            el && el.addEventListener('change', updateQuoteLink);
        });

        function calculate() {
            var size    = sizeEl.value;
            var bays    = parseInt(baysEl.value, 10);
            var install = installEl.value === 'yes';
            var p       = pricing[size];
            var base    = p.first + (p.extra * Math.max(0, bays - 1));
            var instCost = install ? (p.install * bays) : 0;
            var total   = base + instCost;

            lastTotal = total;

            totalEl.textContent = fmt(total);

            var breakdown = (p.label || size) + ' × ' + bays + ' bay' + (bays > 1 ? 's' : '') + '<br>'
                + 'Base: ' + fmt(base);
            if (install) breakdown += '<br>Installation: ' + fmt(instCost);
            breakdownEl.innerHTML = breakdown;

            updateQuoteLink();
            result.hidden = false;

            // Analytics: calculator_start on first calculate click
            track('calculator_start', 'Calculator', size + ' × ' + bays + ' bays', total);

            if (window.innerWidth < 768) {
                setTimeout(function () {
                    result.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        }

        btn.addEventListener('click', calculate);

        // Recalculate if result already showing and user changes a select
        [sizeEl, baysEl, installEl].forEach(function (el) {
            el && el.addEventListener('change', function () {
                if (!result.hidden) calculate();
            });
        });

        // Analytics: calculator_quote_prefill when CTA clicked
        if (quoteLink) {
            quoteLink.addEventListener('click', function () {
                track('calculator_quote_prefill', 'Calculator', sizeEl.value + ' × ' + baysEl.value + ' bays', lastTotal || 0);
            });
        }
    });

    function track(event, category, label, value) {
        if (typeof gtag !== 'undefined') {
            gtag('event', event, { event_category: category, event_label: label, value: value });
        }
        if (typeof dataLayer !== 'undefined') {
            dataLayer.push({ event: event, eventCategory: category, eventLabel: label, eventValue: value });
        }
    }

})();
