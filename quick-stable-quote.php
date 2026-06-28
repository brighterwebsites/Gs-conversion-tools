<?php
/**
 * SIMPLE STABLE PRICE CALCULATOR
 * For product pages - no email gate, just helpful pricing
 * Shortcode: [simple_stable_calc]
 * 
 * Add to Breakdance Advanced Scripts
 */

add_shortcode('simple_stable_calc', function($atts) {
    $atts = shortcode_atts(array(
        'default_size' => '4x4',  // Can pre-select size based on page
        'default_bays' => '1',    // Preselect bay count
        'show_title'   => 'no',
        'quote_url'    => '/stable-quote/'  // Quote form page path (params added by JS)
    ), $atts);
    
    ob_start();
    ?>
    
    <style>
    /* Simple Stable Calculator Styles */
    .gs-simple-calc {
        --gs-red: #c41e3a;
        --gs-dark: #1a1a1a;
        --gs-gray: #4a4a4a;
        --gs-light: #f8f9fa;
        --gs-border: #e5e7eb;
        max-width: 100%;
        margin: 0 auto;
        background: #fff;
        border: 2px solid var(--gs-border);
        border-radius: 0px;
        padding: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    align-content: flex-start;
    gap: 4%;
    align-items: center;
    }
    
    .gs-simple-calc h3 {
        margin: 0;
        font-size: clamp(18px, 4vw, 24px);
        color: var(--gs-dark);
        text-align: center;
        width: 100%;
    }
    .gs-simple-field {
        margin-bottom: 16px;
               width: 48%;
    }
    
    .gs-simple-field label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: var(--gs-gray);
        margin-bottom: 6px;
    }
    
    .gs-simple-field select {
        width: 100%;
        padding: 12px 14px;
        border: 2px solid var(--gs-border);
        border-radius: 0px;
        font-size: 15px;
        background: #fff;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .gs-simple-field select:focus {
        outline: none;
        border-color: var(--gs-red);
    }
    
    .gs-simple-calc-btn {
        width: 100%;
        padding: 14px;
        background:  transparent;
        color: var(--bde-palette-main-dark-1990da94-065b-4239-a657-a6a65ec831c5-6);
        border: 2px solid var(--bde-palette-main-dark-1990da94-065b-4239-a657-a6a65ec831c5-6);
        border-radius: 0px;
       
        cursor: pointer;
        transition: all 0.2s;
        margin-top: 12px;
               width: 48%;
 
    
    font-family: "Barlow Condensed", sans-serif;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;

    }
    

    
    
    
    .gs-simple-calc-btn:hover {
        background: var(--bde-palette-blue-773fad13-c7ab-4b56-99d3-4451110c40f2-36);
        color: var(--bde-palette-2-560568b5-9659-418a-bf7c-e20fae003590-9)!important;
        transform: translateY(-1px);
      border: 2px solid var(--bde-palette-2-560568b5-9659-418a-bf7c-e20fae003590-9);
    }
    
    .gs-simple-result {
        display: none;
        margin-top: 24px;
        padding: 20px;
        background: var(--gs-light);
        border-radius: 0px;
        border-left: 4px solid var(--gs-red);
               width: 100%;
    }
    
    .gs-simple-result.active {
        display: block;
        animation: slideIn 0.3s ease;
               width: 100%;
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .gs-simple-total {
        font-size: clamp(24px, 6vw, 25px);
        font-weight: 700;
        color: var(--gs-red);
        text-align: center;
        margin-bottom: 12px;
    }
    
    .gs-simple-breakdown {
        font-size: 13px;
        color: var(--gs-gray);
        text-align: center;
        margin-bottom: 16px;
        line-height: 1.6;
    }
    
    .gs-simple-cta {
        display: block;
        text-align: center;
        padding: 12px 20px;
        background: var(--gs-dark);
        color: #fff!important;
        text-decoration: none;
        border-radius: 0px;

        transition: all 0.2s;
               width: 100%;
               font-family: "Barlow Condensed", sans-serif;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    }
    
    .gs-simple-cta:hover {
        background: #000;
        transform: translateY(-1px);
    }
    
    .gs-simple-note {
        margin-top: 12px;
        font-size: 12px;
        color: var(--gs-gray);
        text-align: center;
        font-style: italic;
               width: 100%;
    }
    
    @media (max-width: 480px) {
        .gs-simple-calc {
            padding: 15px 18px;
            flex-direction: column;
        }
            .gs-simple-calc-btn,
    .gs-simple-field{
          width: 100%;
    }
    }
    </style>
        <div class="gs-simple-calc" data-default-size="<?php echo esc_attr($atts['default_size']); ?>" data-default-bays="<?php echo esc_attr($atts['default_bays']); ?>" data-quote-url="<?php echo esc_attr(isset($atts['quote_url']) ? $atts['quote_url'] : '/stable-quote/'); ?>">
        <?php if ($atts['show_title'] === 'yes') : ?>
            <h3>Quick Price Calculator</h3>
            <p>Get an instant ballpark price based on size and number of horses.
</p>
        <?php endif; ?>
        
        <div class="gs-simple-field">
            <label for="gs-simple-size">Stable Size</label>
            <select id="gs-simple-size" class="ga-trust-pricing">
                <option value="4x4">4m × 4m Standard</option>
                <option value="5x4">5m × 4m Large</option>
                <option value="4x5">4m × 5m Large</option>
                <option value="5x5">5m × 5m XLarge</option>
            </select>
        </div>
        
        <div class="gs-simple-field">
            <label for="gs-simple-bays">Number of Bays</label>
            <select id="gs-simple-bays" class="ga-trust-pricing">
                <option value="1">1 bay</option>
                <option value="2">2 bays</option>
                <option value="3">3 bays</option>
                <option value="4">4 bays</option>
                <option value="5">5 bays</option>
            </select>
        </div>
        
        <div class="gs-simple-field">
            <label for="gs-simple-install">Professional Installation</label>
            <select id="gs-simple-install" class="ga-trust-pricing">
                <option value="yes">Yes - Include installation</option>
                <option value="no">No - DIY / Self-install</option>
            </select>
        </div>
        
        <button class="gs-simple-calc-btn ga-cta-micro" id="gs-simple-btn">
            Calculate Price
        </button>
        
        <div class="gs-simple-result" id="gs-simple-result">
            <div class="gs-simple-total" id="gs-simple-total">$0</div>
            <div class="gs-simple-breakdown" id="gs-simple-breakdown"></div>
            <a href="#" id="gs-simple-quote-link" class="gs-simple-cta ga-cta-main">Get Detailed Quote →</a>
            <div class="gs-simple-note">
                Price includes GST. Delivery and custom options available.
            </div>
        </div>
    </div>
    
    <script>
    (function() {
        'use strict';
        
        const wrapper = document.querySelector('.gs-simple-calc');
        if (!wrapper) return;
        
        // Use central pricing config (or fallback)
        const config = window.GS_PRICING_CONFIG || {
            base: {
                '4x4': { first: 4500, extra: 4000, install: 400, label: '4m × 4m Standard' },
                '5x4': { first: 6100, extra: 5900, install: 550, label: '5m × 4m Large' },
                '4x5': { first: 6100, extra: 5900, install: 550, label: '4m × 5m Large' },
                '5x5': { first: 7800, extra: 6200, install: 650, label: '5m × 5m XLarge' }
            }
        };
        
        const pricing = config.base;
        const sizeLabels = {};
        Object.keys(pricing).forEach(key => {
            sizeLabels[key] = pricing[key].label;
        });
        
        // Elements
        const sizeSelect = document.getElementById('gs-simple-size');
        const baysSelect = document.getElementById('gs-simple-bays');
        const installSelect = document.getElementById('gs-simple-install');
        const calcBtn = document.getElementById('gs-simple-btn');
        const result = document.getElementById('gs-simple-result');
        const totalEl = document.getElementById('gs-simple-total');
        const breakdownEl = document.getElementById('gs-simple-breakdown');
        const quoteLink = document.getElementById('gs-simple-quote-link');
        
        // Build quote page URL with params for prefill (quote form reads size, bays, estimated_price, install, source)
        function buildQuoteUrl() {
            const size = sizeSelect.value;
            const bays = baysSelect.value;
            const install = installSelect.value;
            const base = (wrapper.dataset.quoteUrl || '/stable-quote/').replace(/\/?$/, '');
            const params = new URLSearchParams({
                size: size,
                bays: bays,
                source: 'calculator'
            });
            // Only add estimated_price after user has calculated (we'll set link on first calculate)
            if (wrapper._lastTotal != null) {
                params.set('estimated_price', String(wrapper._lastTotal));
                params.set('install', install);
            }
            return base + '?' + params.toString();
        }
        
        function updateQuoteLink() {
            if (quoteLink) {
                quoteLink.href = buildQuoteUrl();
            }
        }
        
        // Set default size if specified
        const defaultSize = wrapper.dataset.defaultSize;
        const defaultBays = wrapper.dataset.defaultBays;
        if (defaultSize && sizeSelect.querySelector(`option[value="${defaultSize}"]`)) {
            sizeSelect.value = defaultSize;
        }
        if (defaultBays && baysSelect.querySelector(`option[value="${defaultBays}"]`)) {
            baysSelect.value = defaultBays;
        }
        
        // Set initial quote link; keep in sync when size/bays/install change
        updateQuoteLink();
        [sizeSelect, baysSelect, installSelect].forEach(function(el) {
            if (el) el.addEventListener('change', updateQuoteLink);
        });
        
        function formatPrice(num) {
            return '$' + num.toLocaleString('en-AU');
        }
        
        function calculate() {
            const size = sizeSelect.value;
            const bays = parseInt(baysSelect.value);
            const includeInstall = installSelect.value === 'yes';
            
            const p = pricing[size];
            
            // Calculate base price
            const basePrice = p.first + (p.extra * Math.max(0, bays - 1));
            
            // Calculate install
            const installPrice = includeInstall ? (p.install * bays) : 0;
            
            // Total
            const total = basePrice + installPrice;
            wrapper._lastTotal = total;
            
            // Update UI
            totalEl.textContent = formatPrice(total);
            
            // Update quote link so it prefills the form with this configuration
            updateQuoteLink();
            
            // Breakdown text
            let breakdown = `${sizeLabels[size]} × ${bays} bay${bays > 1 ? 's' : ''}<br>`;
            breakdown += `Base: ${formatPrice(basePrice)}`;
            if (includeInstall) {
                breakdown += `<br>Installation: ${formatPrice(installPrice)}`;
            }
            
            breakdownEl.innerHTML = breakdown;
            
            // Show result
            result.classList.add('active');
            
            // Track in GA
            if (typeof gtag !== 'undefined') {
                gtag('event', 'calculator_used', {
                    event_category: 'Calculator',
                    event_label: `${size} × ${bays} bays`,
                    value: total
                });
            }
            
            // Scroll to result on mobile
            if (window.innerWidth < 768) {
                setTimeout(() => {
                    result.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        }
        
        // Event listeners
        calcBtn.addEventListener('click', calculate);
        
        // Auto-calculate on change (optional - comment out if you want manual only)
        [sizeSelect, baysSelect, installSelect].forEach(el => {
            el.addEventListener('change', () => {
                if (result.classList.contains('active')) {
                    calculate();
                }
            });
        });
        
    })();
    </script>
    
    <?php
    return ob_get_clean();
});
?>


