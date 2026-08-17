/* global GS_PRICING_CONFIG, GS_QUIZ_CONFIG, gtag, dataLayer */
(function () {
    'use strict';

    var root = document.querySelector('.gs-quiz');
    if (!root) return;

    // ── Config ──────────────────────────────────────────────────────────────
    var QUIZ_CFG = window.GS_QUIZ_CONFIG || {};
    var QUOTE_URL     = QUIZ_CFG.quoteUrl     || '/stables/quote/';
    var LEARN_MORE_URL = QUIZ_CFG.learnMoreUrl || '/stables-base-model-compare';

    var PC = (window.GS_PRICING_CONFIG && window.GS_PRICING_CONFIG.base) || {
        '4x4': { first: 4500, extra: 4000, install: 550, label: '4m × 4m Standard' },
        '5x4': { first: 6100, extra: 5900, install: 700, label: '5m × 4m Large' },
        '4x5': { first: 6100, extra: 5900, install: 700, label: '4m × 5m Large' },
        '5x5': { first: 7800, extra: 6200, install: 850, label: '5m × 5m XLarge' },
    };
    var UPGRADES = (window.GS_PRICING_CONFIG && window.GS_PRICING_CONFIG.upgrades) || {
        pitchRoof: 450,
        anchors:   100,
    };

    // ── State ────────────────────────────────────────────────────────────────
    var state = {
        current:      1,
        total:        6,
        answers:      {},
        started:      false,   // true after first option selected
        recommendation: null,
    };

    // ── DOM refs ─────────────────────────────────────────────────────────────
    var questionsWrap = root.querySelector('#gs-questions');
    var loadingEl     = root.querySelector('#gs-loading');
    var resultsEl     = root.querySelector('#gs-results');
    var fillEl        = root.querySelector('#gs-q-fill');
    var numEl         = root.querySelector('#gs-q-num');
    var pctEl         = root.querySelector('#gs-q-pct');
    var questions     = root.querySelectorAll('.gs-quiz__question');

    // ── Boot ─────────────────────────────────────────────────────────────────
    root.querySelectorAll('.gs-quiz__option').forEach(function (opt) {
        opt.addEventListener('click', onOptionClick);
    });
    root.querySelectorAll('.gs-quiz__btn--next').forEach(function (btn, i) {
        btn.addEventListener('click', function () { onNext(i + 1); });
    });
    root.querySelectorAll('.gs-quiz__btn--back').forEach(function (btn, i) {
        btn.addEventListener('click', function () { onBack(i + 1); });
    });

    updateProgress();

    // ── Handlers ─────────────────────────────────────────────────────────────
    function onOptionClick(e) {
        var opt = e.currentTarget;
        var qEl = opt.closest('.gs-quiz__question');
        var qNum = parseInt(qEl.dataset.question, 10);

        // Fire quiz_start only on first ever selection
        if (!state.started) {
            state.started = true;
            track('quiz_start', 'Quiz', 'Quiz Started', 5);
        }

        qEl.querySelectorAll('.gs-quiz__option').forEach(function (o) {
            o.classList.remove('selected');
        });
        opt.classList.add('selected');
        state.answers['q' + qNum] = opt.dataset.value;

        var nextBtn = qEl.querySelector('.gs-quiz__btn--next');
        if (nextBtn) nextBtn.disabled = false;

        track('quiz_q_answered', 'Quiz', 'Q' + qNum + ': ' + opt.dataset.value, 2);
    }

    function onNext(qNum) {
        if (qNum === state.total) {
            showResults();
        } else {
            state.current = qNum + 1;
            showQuestion(state.current);
        }
    }

    function onBack(qNum) {
        if (qNum > 1) {
            state.current = qNum - 1;
            showQuestion(state.current);
        }
    }

    function showQuestion(num) {
        questions.forEach(function (q) { q.classList.remove('active'); });
        var target = root.querySelector('.gs-quiz__question[data-question="' + num + '"]');
        if (!target) return;
        target.classList.add('active');

        var saved = state.answers['q' + num];
        if (saved) {
            var savedOpt = target.querySelector('[data-value="' + saved + '"]');
            if (savedOpt) savedOpt.classList.add('selected');
            var nextBtn = target.querySelector('.gs-quiz__btn--next');
            if (nextBtn) nextBtn.disabled = false;
        }

        numEl.textContent = num;
        updateProgress();
    }

    function updateProgress() {
        var pct = Math.round((state.current / state.total) * 100);
        if (fillEl) fillEl.style.width = pct + '%';
        if (pctEl)  pctEl.textContent  = pct + '%';
        if (numEl)  numEl.textContent  = state.current;
    }

    // ── Recommendation engine ─────────────────────────────────────────────────
    function calcRecommendation() {
        var ans = state.answers;
        var rec = {
            model:     '',
            size:      '4x4',
            bays:      1,
            basePrice: 0,
            upgrades:  [],
            total:     0,
            features:  [],
            why:       '',
            learnMoreUrl:   LEARN_MORE_URL + '#standard',
            learnMoreTitle: 'Standard Base Model Stable',
            quoteUrl:  '',
        };

        rec.bays = parseInt(ans.q2, 10) || 1;

        if (ans.q3 === 'maternity') {
            rec.size = '5x5';
            rec.model = '5×5m XL Maternity Stable';
            rec.learnMoreTitle = 'XL Maternity Stable';
            rec.learnMoreUrl = LEARN_MORE_URL + '#maternity';
        } else if (ans.q3 === 'large' || ans.q3 === 'mixed') {
            rec.size = '5x4';
            rec.model = '5×4m Large Steel Horse Stable';
            rec.learnMoreTitle = 'Large Steel Horse Stable';
            rec.learnMoreUrl = LEARN_MORE_URL + '#large';
        } else {
            rec.size = '4x4';
            rec.model = '4×4m Standard Base Model Stable';
            rec.learnMoreTitle = 'Standard Base Model Stable';
            rec.learnMoreUrl = LEARN_MORE_URL + '#standard';
        }

        if (rec.bays > 1) {
            rec.model = rec.bays + '-Bay Modular Stable System (' + rec.size + 'm each)';
            rec.learnMoreTitle = 'Modular Stable System';
            rec.learnMoreUrl = '/stables/multi-stable-systems';
        }

        if (ans.q1 === 'retrofit') {
            rec.learnMoreTitle = 'Shed Retrofit Stables';
            rec.learnMoreUrl = '/stables/retrofit-stable-design';
        }

        var sc = PC[rec.size];
        rec.basePrice = sc.first + (sc.extra * Math.max(0, rec.bays - 1));
        rec.total = rec.basePrice;

        if (ans.q4 === 'dirt' || ans.q4 === 'soft' || ans.q4 === 'undecided') {
            var anchorCost = UPGRADES.anchors * rec.bays;
            rec.upgrades.push({ name: 'Ground anchors (recommended for stability)', price: anchorCost, included: true });
            rec.total += anchorCost;
        }

        if (ans.q5 === 'hot') {
            var roofCost = UPGRADES.pitchRoof * rec.bays;
            rec.upgrades.push({ name: 'Slanted roof upgrade (maximum airflow)', price: roofCost, included: true });
            rec.total += roofCost;
        }

        rec.upgrades.push({ name: 'Professional installation (optional)', price: sc.install * rec.bays, included: false });

        rec.features = [
            (rec.bays === 1 ? 'Single' : rec.bays + '-bay') + ' ' + rec.size + 'm configuration',
            'Heavy-duty galvanised steel frame (50×50mm RHS)',
            'Ply lower panels, mesh upper for ventilation',
            '2.5m high stalls for airflow and comfort',
            'Includes ' + rec.bays + ' straight gate' + (rec.bays > 1 ? 's' : ''),
            ans.q4 === 'concrete' ? 'Concrete ready (dynabolts recommended)' : 'Ground anchor system included',
        ];

        if ((ans.q6 === 'tack' || ans.q6 === 'both') && rec.size === '5x4') {
            rec.features.push('💡 Perfect layout for adding 4m tack room later');
        }

        rec.why = buildWhy(ans, rec);
        rec.quoteUrl = buildQuoteUrl(ans, rec);

        return rec;
    }

    function buildWhy(ans, rec) {
        var txt = 'Based on your answers, we recommend ';
        txt += rec.bays === 1 ? 'a single ' + rec.size + 'm stable ' : 'a ' + rec.bays + '-bay modular system with ' + rec.size + 'm stalls ';
        txt += 'because ';

        var reasons = [];
        if (ans.q3 === 'maternity') reasons.push('you need extra space for foaling and mare comfort');
        else if (ans.q3 === 'large') reasons.push('your large horses need generous room to prevent stress');
        else if (ans.q3 === 'pony') reasons.push('this size is perfect for ponies — spacious without being excessive');
        else if (ans.q3 === 'standard') reasons.push('this is the ideal size for standard riding horses');

        if (ans.q5 === 'hot') reasons.push('we\'ve added the slanted roof for maximum airflow in hot conditions');
        else if (ans.q5 === 'wet') reasons.push('the covered design provides excellent rain protection');

        if (ans.q4 === 'dirt' || ans.q4 === 'soft') reasons.push('ground anchors ensure stability on your surface type');
        if (ans.q1 === 'retrofit') reasons.push('our retrofit system adapts to your existing structure');
        if ((ans.q6 === 'tack' || ans.q6 === 'both') && rec.size === '5x4') {
            reasons.push('the 5m front width is perfect for adding a tack room to the side later');
        }

        return txt + (reasons.length ? reasons.join(', ') : 'it best matches your requirements') + '.';
    }

    function buildQuoteUrl(ans, rec) {
        var p = new URLSearchParams({
            size:       rec.size,
            bays:       rec.bays,
            source:     'quiz',
            starting:   ans.q1 || '',
            horses:     ans.q2 || '',
            horse_size: ans.q3 || '',
            surface:    ans.q4 || '',
            climate:    ans.q5 || '',
            additions:  ans.q6 || '',
            anchors:    (ans.q4 === 'dirt' || ans.q4 === 'soft' || ans.q4 === 'undecided') ? 'yes' : 'no',
            roof:       ans.q5 === 'hot' ? 'slanted' : 'standard',
            estimated_price: rec.total,
        });
        return QUOTE_URL + '?' + p.toString();
    }

    // ── Results ──────────────────────────────────────────────────────────────
    function showResults() {
        questionsWrap.hidden = true;
        loadingEl.hidden = false;

        setTimeout(function () {
            var rec = calcRecommendation();
            state.recommendation = rec;

            // quiz_complete = recommendation shown
            track('quiz_complete', 'Quiz', 'Quiz Completed — ' + rec.model, 30);

            renderResults(rec);
            loadingEl.hidden = true;
            resultsEl.hidden = false;
            resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 1600);
    }

    function renderResults(rec) {
        var sc = PC[rec.size];
        var installCost = sc.install * rec.bays;
        var totalInstalled = rec.total + installCost;

        var upgradesHtml = rec.upgrades
            .filter(function (u) { return u.included; })
            .map(function (u) {
                return '<div class="gs-quiz__price-row"><span class="gs-quiz__price-label">+ ' + u.name + '</span>'
                    + '<span class="gs-quiz__price-value">$' + u.price.toLocaleString('en-AU') + '</span></div>';
            }).join('');

        resultsEl.innerHTML =
            '<div class="gs-quiz__results-header">'
            + '<div class="gs-quiz__results-icon">🎉</div>'
            + '<h2 class="gs-quiz__results-title">We Found a Match!</h2>'
            + '<p class="gs-quiz__results-sub">Here\'s your personalised stable recommendation</p>'
            + '</div>'

            + '<div class="gs-quiz__rec-card">'
            + '<div class="gs-quiz__rec-model">' + rec.model + '</div>'
            + '<ul class="gs-quiz__rec-features">'
            + rec.features.map(function (f) { return '<li>' + f + '</li>'; }).join('')
            + '</ul></div>'

            + '<div class="gs-quiz__pricing">'
            + '<div class="gs-quiz__price-row"><span class="gs-quiz__price-label">Base stable (' + rec.bays + ' bay' + (rec.bays > 1 ? 's' : '') + ')</span><span class="gs-quiz__price-value">$' + rec.basePrice.toLocaleString('en-AU') + '</span></div>'
            + upgradesHtml
            + '<div class="gs-quiz__price-row gs-quiz__price-total"><span class="gs-quiz__price-label">Your Estimated Total:</span><span class="gs-quiz__price-value">$' + rec.total.toLocaleString('en-AU') + '</span></div>'
            + '<div class="gs-quiz__price-row gs-quiz__price-optional"><span class="gs-quiz__price-label">+ Installation (optional)</span><span class="gs-quiz__price-value">$' + installCost.toLocaleString('en-AU') + '</span></div>'
            + '<div class="gs-quiz__price-row"><span class="gs-quiz__price-label">Total Installed:</span><span class="gs-quiz__price-value gs-quiz__price-muted">$' + totalInstalled.toLocaleString('en-AU') + '</span></div>'
            + '<p class="gs-quiz__price-note">All prices include GST</p>'
            + '</div>'

            + '<div class="gs-quiz__why">'
            + '<div class="gs-quiz__why-title">💡 Why this recommendation?</div>'
            + '<p>' + rec.why + '</p>'
            + '</div>'

            + '<div class="gs-quiz__ctas">'
            + '<a href="' + rec.quoteUrl + '" class="gs-quiz__cta-primary js-quiz-cta-quote">Continue to My Custom Quote →</a>'
            + '<p class="gs-quiz__cta-note">We have pre-filled your details — just review and submit!</p>'
            + '<a href="' + rec.learnMoreUrl + '" class="gs-quiz__cta-secondary js-quiz-cta-learn">🔵 Learn More About ' + rec.learnMoreTitle + '</a>'
            + '</div>'

            + '<div class="gs-quiz__social-proof">'
            + '<strong>★★★★★ 5.0 rating</strong> · 17 reviews (13 Google + 4 Facebook)<br>'
            + '<strong>60+ stables delivered</strong> in the last 12 months'
            + '</div>';

        // CTA analytics
        var quoteBtn = resultsEl.querySelector('.js-quiz-cta-quote');
        var learnBtn = resultsEl.querySelector('.js-quiz-cta-learn');

        if (quoteBtn) {
            quoteBtn.addEventListener('click', function () {
                // quiz_quote_prefill = user clicked to go get a quote
                track('quiz_quote_prefill', 'Quiz', rec.size + ' × ' + rec.bays + ' bays', rec.total);
            });
        }
        if (learnBtn) {
            learnBtn.addEventListener('click', function () {
                // quiz_recommendation_seen = user clicked to view the recommended product page
                track('quiz_recommendation_seen', 'Quiz', rec.model, rec.total);
            });
        }
    }

    // ── Analytics ────────────────────────────────────────────────────────────
    function track(event, category, label, value) {
        if (typeof gtag !== 'undefined') {
            gtag('event', event, { event_category: category, event_label: label, value: value });
        }
        if (typeof dataLayer !== 'undefined') {
            dataLayer.push({ event: event, eventCategory: category, eventLabel: label, eventValue: value });
        }
        if (typeof console !== 'undefined') {
            console.log('GS Quiz:', event, label, value);
        }
    }

    // Show first question
    showQuestion(1);

})();
