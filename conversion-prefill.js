/**
 * ================================================
 * GUERILLA STEEL - QUOTE FORM PREFILL SCRIPT
 * ================================================
 * 
 * This script reads URL parameters from the quiz
 * and pre-fills your quote form fields.
 * 
 * INSTALLATION:
 * Add this to your /quote/ page in Breakdance:
 * - Advanced Scripts > JavaScript section
 * - OR as a Code Block element at bottom of page
 * 
 * ================================================
 */

(function() {
    'use strict';
    
    console.log('🎯 Quote Form Prefill Script Loaded');
    
    // ===== PARSE URL PARAMETERS =====
    function getUrlParams() {
        const params = new URLSearchParams(window.location.search);
        const quizData = {};
        
        for (const [key, value] of params) {
            quizData[key] = value;
        }
        
        return quizData;
    }
    
    // ===== SHOW QUIZ BANNER =====
    function showQuizBanner(data) {
        // Show banner when we have size (quiz) or estimated_price (calculator)
        if (!data.size && !data.estimated_price) return;
        
        const banner = document.createElement('div');
        banner.className = 'gs-quiz-banner';
        banner.innerHTML = `
            <style>
                .gs-quiz-banner {
                    background: linear-gradient(135deg, #c41e3a, #a01730);
                    color: #fff;
                    padding: 16px 20px;
                    border-radius: 8px;
                    margin-bottom: 24px;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    box-shadow: 0 2px 8px rgba(196, 30, 58, 0.2);
                }
                .gs-quiz-banner-icon {
                    font-size: 28px;
                }
                .gs-quiz-banner-text {
                    flex: 1;
                }
                .gs-quiz-banner-title {
                    font-size: 16px;
                    font-weight: 700;
                    margin-bottom: 4px;
                }
                .gs-quiz-banner-subtitle {
                    font-size: 14px;
                    opacity: 0.95;
                }
                @media (max-width: 600px) {
                    .gs-quiz-banner {
                        flex-direction: column;
                        text-align: center;
                    }
                }
            </style>
            <div class="gs-quiz-banner-icon">✅</div>
            <div class="gs-quiz-banner-text">
                <div class="gs-quiz-banner-title">Based on Your Results</div>
                <div class="gs-quiz-banner-subtitle">
                    ${data.size ? `We've pre-filled this form with your ${data.size} ${data.bays}-bay configuration` : 'We\'ve pre-filled this form with your calculator estimate'}
                </div>
            </div>
        `;
        
        // Insert at top of form
        const form = document.querySelector('form');
        if (form) {
            form.parentNode.insertBefore(banner, form);
        }
    }
    
    // ===== PREFILL FORM FIELDS =====
    function prefillForm(data) {
        console.log('🎯 Prefilling form with quiz data:', data);
        
        // Map quiz/calculator parameters to form field names
        // Form uses: fields[model][] (checkboxes), fields[bays], fields[type][] (checkboxes), fields[addon][] (checkboxes), fields[message]
        
        const fieldMappings = {
            // Number of bays
            'bays': {
                selector: 'input[name="fields[bays]"]',
                value: data.bays
            },
            
            // Base Model Size - CHECKBOXES name="fields[model][]" values: 4x4, 5x4, 5x5
            'size': {
                type: 'checkbox-single',
                name: 'fields[model][]',
                value: data.size
            },
            
            // Structure Type - CHECKBOXES name="fields[type][]" values: Stable, Open Shelter, Retrofit, Custom Design, Rail
            'starting': {
                type: 'checkbox-single',
                name: 'fields[type][]',
                value: (data.starting === 'retrofit' ? 'Retrofit' : 'Stable')
            },
            
            // Special notes / message - form uses fields[message]
            'special': {
                selector: 'textarea[name="fields[message]"]',
                append: true,
                value: generateQuizSummary(data)
            },
            
            // Add-Ons - CHECKBOXES name="fields[addon][]" values: Tack Room, Wash Bay, Day Yard, Yoke , Pitch
            'additions': {
                type: 'checkbox-array',
                name: 'fields[addon][]',
                value: data.additions
            },
            // Optional: comma-separated addon values from URL (e.g. addon=Tack Room,Pitch)
            'addon': {
                type: 'checkbox-array-values',
                name: 'fields[addon][]',
                value: data.addon
            }
        };
        
        // Loop through mappings and prefill
        Object.keys(fieldMappings).forEach(key => {
            const mapping = fieldMappings[key];
            // Skip when no data for this key
            if (key === 'addon') {
                if (!data.addon) return;
            } else if (key === 'starting') {
                // Always run: prefill Structure Type (Stable or Retrofit from data.starting)
                mapping.value = (data.starting === 'retrofit' ? 'Retrofit' : 'Stable');
            } else if (key === 'special') {
                // Always run when we have quiz/calculator data: prefill message (e.g. /stable-quote lower-intent form)
                mapping.value = generateQuizSummary(data);
            } else if (!data[key]) {
                return;
            }
            
            // Single checkbox to check (e.g. model size, structure type) - check one by value, uncheck others
            if (mapping.type === 'checkbox-single') {
                prefillCheckboxSingle(mapping.name, mapping.value);
                return;
            }
            
            // Checkbox array by quiz keywords (additions = tack, wash, etc.)
            if (mapping.type === 'checkbox-array') {
                prefillCheckboxArray(mapping.name, mapping.value);
                return;
            }
            
            // Checkbox array by exact values (e.g. addon=Tack Room,Pitch)
            if (mapping.type === 'checkbox-array-values') {
                prefillCheckboxArrayByValues(mapping.name, mapping.value);
                return;
            }
            
            const field = document.querySelector(mapping.selector);
            
            if (!field) {
                console.warn(`⚠️ Field not found: ${mapping.selector}`);
                return;
            }
            
            // Handle different field types
            if (field.tagName === 'SELECT') {
                // Select dropdown
                prefillSelect(field, mapping.value);
            } else if (field.type === 'checkbox' || field.type === 'radio') {
                // Checkbox/Radio
                field.checked = mapping.checked || false;
            } else if (field.tagName === 'TEXTAREA') {
                // Textarea - append or overwrite
                if (mapping.append) {
                    const currentValue = field.value.trim();
                    const newValue = mapping.value;
                    field.value = currentValue ? `${currentValue}\n\n${newValue}` : newValue;
                } else {
                    field.value = mapping.value;
                }
            } else {
                // Text/Number input
                field.value = mapping.value;
            }
            
            // Trigger change event for any JS that listens
            field.dispatchEvent(new Event('change', { bubbles: true }));
            
            console.log(`✅ Prefilled: ${key} = ${mapping.value || mapping.checked}`);
        });
        
        // Add a hidden field with all quiz data for tracking
        addHiddenQuizData(data);
        
        // Scroll to form
        setTimeout(() => {
            const form = document.querySelector('form');
            if (form) {
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 500);
    }
    
    // ===== HELPER: PREFILL SINGLE CHECKBOX BY VALUE (model size, structure type) =====
    function prefillCheckboxSingle(fieldName, targetValue) {
        const checkboxes = document.querySelectorAll(`input[name="${fieldName}"]`);
        if (checkboxes.length === 0) {
            console.warn(`⚠️ No checkboxes found for: ${fieldName}`);
            return false;
        }
        const target = String(targetValue).toLowerCase().trim();
        let matched = false;
        // Form model values: 4x4, 5x4, 5x5 (no 4x5) - map 4x5 to 5x4 so one option is selected
        const isModel = fieldName.indexOf('model') !== -1;
        const compareVal = isModel && target === '4x5' ? '5x4' : target;
        checkboxes.forEach(cb => {
            const val = String(cb.value || '').toLowerCase().trim();
            const match = (val === compareVal);
            cb.checked = !!match;
            if (match) matched = true;
        });
        if (matched) {
            console.log(`✅ Prefilled ${fieldName}: ${targetValue}`);
        } else {
            console.warn(`⚠️ Could not match checkbox for ${fieldName} = ${targetValue}. Available: ${Array.from(checkboxes).map(cb => cb.value).join(', ')}`);
        }
        return matched;
    }

    // ===== HELPER: PREFILL CHECKBOX ARRAY BY EXACT VALUES (e.g. addon=Tack Room,Pitch) =====
    function prefillCheckboxArrayByValues(fieldName, valueString) {
        const checkboxes = document.querySelectorAll(`input[name="${fieldName}"]`);
        if (checkboxes.length === 0) {
            console.warn(`⚠️ No checkboxes found for: ${fieldName}`);
            return;
        }
        const values = String(valueString || '').split(',').map(v => v.trim()).filter(Boolean);
        checkboxes.forEach(cb => {
            const val = String(cb.value || '').trim();
            cb.checked = values.some(v => val === v || val.toLowerCase() === v.toLowerCase());
        });
        console.log(`✅ Prefilled ${fieldName} with: ${values.join(', ')}`);
    }

    // ===== HELPER: PREFILL RADIO BUTTON ARRAYS (legacy - form uses checkboxes now) =====
    function prefillRadioArray(fieldName, targetValue) {
        // Find all radio buttons with this name
        const radios = document.querySelectorAll(`input[name="${fieldName}"]`);
        
        if (radios.length === 0) {
            console.warn(`⚠️ No radio buttons found for: ${fieldName}`);
            return;
        }
        
        // Normalize target value for comparison
        const target = String(targetValue).toLowerCase().trim();
        
        let matched = false;
        
        // ===== BASE MODEL MATCHING =====
        // Labels: "4m × 4m Standard ($4,500)", "5m × 4m Large ($6,100)", "5m × 5m XLarge ($7,800)"
        if (fieldName.includes('BaseModel')) {
            const sizeMap = {
                '4x4': 'BaseModel-1',  // 4m × 4m Standard
                '5x4': 'BaseModel-2',  // 5m × 4m Large
                '4x5': 'BaseModel-2',  // Same as 5x4 (5m × 4m Large)
                '5x5': 'BaseModel-3'   // 5m × 5m XLarge
            };
            
            const targetId = sizeMap[target];
            
            radios.forEach(radio => {
                if (radio.id === targetId) {
                    radio.checked = true;
                    matched = true;
                    const label = radio.nextElementSibling ? radio.nextElementSibling.textContent.trim() : radio.id;
                    console.log(`✅ Selected Base Model: ${label}`);
                }
            });
        }
        
        // ===== STRUCTURE TYPE MATCHING =====
        // Labels: "Stable", "Open Shelter", "Retrofit", "Custom Design"
        else if (fieldName.includes('StructureType')) {
            radios.forEach(radio => {
                const label = radio.nextElementSibling ? radio.nextElementSibling.textContent.toLowerCase().trim() : '';
                
                // Match retrofit
                if (target === 'retrofit' && label.includes('retrofit')) {
                    radio.checked = true;
                    matched = true;
                    console.log(`✅ Selected Structure Type: Retrofit`);
                }
                // Match new build (defaults to "Stable")
                else if ((target === 'new' || target === 'unsure') && label.includes('stable')) {
                    radio.checked = true;
                    matched = true;
                    console.log(`✅ Selected Structure Type: Stable`);
                }
            });
        }
        
        if (!matched) {
            console.warn(`⚠️ Could not match radio button for ${fieldName} = ${targetValue}`);
            console.log('Available radios:', Array.from(radios).map(r => ({
                id: r.id,
                value: r.value,
                label: r.nextElementSibling ? r.nextElementSibling.textContent.trim() : 'no-label'
            })));
        }
        
        return matched;
    }
    
    // ===== HELPER: PREFILL CHECKBOX ARRAYS =====
    function prefillCheckboxArray(fieldName, targetValue) {
        // Find all checkboxes with this name
        const checkboxes = document.querySelectorAll(`input[name="${fieldName}"]`);
        
        if (checkboxes.length === 0) {
            console.warn(`⚠️ No checkboxes found for: ${fieldName}`);
            return;
        }
        
        // Normalize target value
        const target = String(targetValue).toLowerCase().trim();
        
        let matched = false;
        
        // ===== ADD-ON MATCHING =====
        // Labels: "Tack Room", "Wash Bay", "Crush", "Day Yard", "Breezeway"
        if (fieldName.includes('AddOn')) {
            checkboxes.forEach(checkbox => {
                const label = checkbox.nextElementSibling ? checkbox.nextElementSibling.textContent.toLowerCase().trim() : '';
                
                // Check tack room
                if ((target === 'tack' || target === 'both') && label.includes('tack')) {
                    checkbox.checked = true;
                    matched = true;
                    console.log(`✅ Selected Add-On: Tack Room`);
                }
                
                // Check wash bay
                if ((target === 'wash' || target === 'both') && label.includes('wash')) {
                    checkbox.checked = true;
                    matched = true;
                    console.log(`✅ Selected Add-On: Wash Bay`);
                }
            });
        }
        
        if (!matched) {
            console.log(`ℹ️ No add-ons selected (quiz answer: ${targetValue})`);
        }
        
        return matched;
    }
    
    // ===== HELPER: PREFILL SELECT DROPDOWN =====
    function prefillSelect(selectElement, targetValue) {
        const options = selectElement.options;
        
        for (let i = 0; i < options.length; i++) {
            const optionValue = options[i].value.toLowerCase();
            const optionText = options[i].text.toLowerCase();
            const target = String(targetValue).toLowerCase();
            
            // Match by value or text
            if (optionValue === target || optionText.includes(target) || target.includes(optionValue)) {
                selectElement.selectedIndex = i;
                return true;
            }
        }
        
        console.warn(`⚠️ Could not find matching option for: ${targetValue}`);
        return false;
    }
    
    // ===== HELPER: ADD HIDDEN TRACKING FIELDS =====
    function addHiddenQuizData(data) {
        const form = document.querySelector('form');
        if (!form) return;
        
        // Add hidden field with quiz data JSON
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'quiz_data';
        hiddenField.value = JSON.stringify(data);
        form.appendChild(hiddenField);
        
        // Add individual hidden fields for easier form processing
        Object.keys(data).forEach(key => {
            const field = document.createElement('input');
            field.type = 'hidden';
            field.name = `quiz_${key}`;
            field.value = data[key];
            form.appendChild(field);
        });
        
        console.log('✅ Added hidden quiz data fields to form');
    }
    
    // Size key → human label for calculator message
    const SIZE_LABELS = { '4x4': '4m × 4m Standard', '5x4': '5m × 4m Large', '4x5': '4m × 5m Large', '5x5': '5m × 5m XLarge' };

    // ===== HELPER: GENERATE QUIZ SUMMARY =====
    function generateQuizSummary(data) {
        const parts = [];
        const fromCalculator = data.source === 'calculator' || (data.estimated_price && !data.horses);
        
        if (fromCalculator) {
            // Calculator: prefill "Please note any special Requirements" on /stable-quote (lower-intent form)
            const label = SIZE_LABELS[data.size] || data.size || '';
            const bays = data.bays || '1';
            const fmt = (n) => Number(n || 0).toLocaleString('en-AU');
            const base = data.base_price != null ? fmt(data.base_price) : '';
            const install = data.install_price != null && Number(data.install_price) > 0 ? fmt(data.install_price) : '';
            const total = data.estimated_price != null ? fmt(data.estimated_price) : '';
            const compact = data.format === 'compact';
            
            if (compact) {
                // Single-line fallback if line breaks aren't possible (e.g. some email/form systems)
                const labelFlat = label.replace(/ × /g, ' by ');
                if (install && data.install === 'yes') {
                    return `${bays} Bay${bays !== '1' ? 's' : ''} ${labelFlat} with installation ${total}`;
                }
                return `${bays} Bay${bays !== '1' ? 's' : ''} ${labelFlat} ${base || total}`;
            }
            parts.push(`${label} × ${bays} bay${bays !== '1' ? 's' : ''}`);
            if (base) parts.push(`Base: $${base}`);
            if (install) parts.push(`Installation: $${install}`);
            if (total) parts.push(`Total: $${total}`);
            return parts.join('\n');
        }
        
        // Quiz
        parts.push('=== FROM STABLE FINDER QUIZ ===');
        parts.push('');
        if (data.size && data.bays) {
            parts.push(`Configuration: ${data.size} × ${data.bays} bay(s)`);
        }
        if (data.estimated_price) {
            parts.push(`Estimated Price: $${Number(data.estimated_price).toLocaleString('en-AU')}`);
        }
        if (data.install) {
            parts.push(`Installation: ${data.install === 'yes' ? 'Yes' : 'No'}`);
        }
        parts.push('');
        parts.push('Quiz Responses:');
        if (data.starting) parts.push(`• Starting Point: ${capitalizeFirst(data.starting)}`);
        if (data.horses) parts.push(`• Number of Horses: ${data.horses}`);
        if (data.horse_size) parts.push(`• Horse Size: ${capitalizeFirst(data.horse_size)}`);
        if (data.surface) parts.push(`• Ground Surface: ${capitalizeFirst(data.surface)}`);
        if (data.climate) parts.push(`• Climate Concern: ${capitalizeFirst(data.climate)}`);
        if (data.additions) parts.push(`• Future Additions: ${capitalizeFirst(data.additions)}`);
        if (data.anchors === 'yes') parts.push('• Anchors: Recommended');
        if (data.roof === 'slanted') parts.push('• Roof: Slanted (for airflow)');
        parts.push('');
        parts.push('===========================');
        return parts.join('\n');
    }
    
    // ===== HELPER: CAPITALIZE FIRST LETTER =====
    function capitalizeFirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    // ===== TRACK PREFILL EVENT =====
    function trackPrefillEvent(data) {
        const event = 'quote_form_prefilled';
        const label = (data.size && data.bays) ? `${data.size} ${data.bays}-bay` : 'Quote prefilled';
        
        // GA4
        if (typeof gtag !== 'undefined') {
            gtag('event', event, {
                event_category: 'Quiz',
                event_label: label,
                value: parseInt(data.estimated_price) || 0
            });
        }
        
        // Universal Analytics
        if (typeof ga !== 'undefined') {
            ga('send', 'event', 'Quiz', event, label);
        }
        
        // GTM
        if (typeof dataLayer !== 'undefined') {
            dataLayer.push({
                'event': event,
                'eventCategory': 'Quiz',
                'eventLabel': label,
                'quizData': data
            });
        }
        
        console.log('📊 Tracked prefill event:', label);
    }
    
    // ===== MAIN EXECUTION =====
    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
    }
    
    function run() {
        const quizData = getUrlParams();
        
        // Run when we have size (quiz) or estimated_price (calculator)
        if (!quizData.size && !quizData.estimated_price) {
            console.log('ℹ️ No quiz/calculator data found in URL');
            return;
        }
        
        console.log('🎯 Quote prefill data:', quizData);
        
        // Track: they reached the recommendation/quote page (one of the 4 events you wanted)
        if (typeof gtag === 'function' && (!window.brighterGA4 || !window.brighterGA4.skipTracking)) {
            gtag('event', 'reached_recommendation_page', {
                event_category: 'Quiz',
                event_label: (quizData.size && quizData.bays) ? `${quizData.size} ${quizData.bays}-bay` : 'Quote with results',
                value: parseInt(quizData.estimated_price, 10) || 0
            });
        }
        
        // Show banner
        showQuizBanner(quizData);
        
        // Prefill form (with small delay to ensure form is loaded)
        setTimeout(() => {
            prefillForm(quizData);
            trackPrefillEvent(quizData);
        }, 300);
    }
    
    // ===== START =====
    init();
    
})();


/**
 * ================================================
 * CUSTOMIZATION GUIDE
 * ================================================
 * 
 * To match your actual form fields, update the
 * fieldMappings object in prefillForm() function.
 * 
 * Example: If your form has a field like:
 * <select name="stable_size">
 * 
 * Then update the selector to:
 * 'size': {
 *     selector: 'select[name="stable_size"]',
 *     value: data.size
 * }
 * 
 * FINDING YOUR FIELD NAMES:
 * 1. Right-click your form field
 * 2. Choose "Inspect"
 * 3. Look for the "name" attribute
 * 4. Update the selector in fieldMappings
 * 
 * ================================================
 */
