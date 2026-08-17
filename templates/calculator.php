<?php
/**
 * Calculator template.
 * Variables available: $atts (shortcode attributes).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$default_size = esc_attr( $atts['default_size'] );
$default_bays = esc_attr( $atts['default_bays'] );
$quote_url    = esc_attr( $atts['quote_url'] );
$show_title   = $atts['show_title'] === 'yes';
?>
<div class="gs-calc"
     data-default-size="<?php echo $default_size; ?>"
     data-default-bays="<?php echo $default_bays; ?>"
     data-quote-url="<?php echo $quote_url; ?>">

    <?php if ( $show_title ) : ?>
        <h3 class="gs-calc__title">Quick Price Calculator</h3>
        <p class="gs-calc__intro">Get an instant ballpark price based on size and number of horses.</p>
    <?php endif; ?>

    <div class="gs-calc__fields">
        <div class="gs-calc__field">
            <label for="gs-calc-size">Stable Size</label>
            <select id="gs-calc-size">
                <option value="4x4">4m × 4m Standard</option>
                <option value="5x4">5m × 4m Large</option>
                <option value="4x5">4m × 5m Large</option>
                <option value="5x5">5m × 5m XLarge</option>
            </select>
        </div>

        <div class="gs-calc__field">
            <label for="gs-calc-bays">Number of Bays</label>
            <select id="gs-calc-bays">
                <option value="1">1 bay</option>
                <option value="2">2 bays</option>
                <option value="3">3 bays</option>
                <option value="4">4 bays</option>
                <option value="5">5 bays</option>
            </select>
        </div>

        <div class="gs-calc__field">
            <label for="gs-calc-install">Professional Installation</label>
            <select id="gs-calc-install">
                <option value="yes">Yes - Include installation</option>
                <option value="no">No - DIY / Self-install</option>
            </select>
        </div>

        <button class="gs-calc__btn" id="gs-calc-btn" type="button">
            Calculate Price
        </button>
    </div>

    <div class="gs-calc__result" id="gs-calc-result" hidden>
        <div class="gs-calc__total"  id="gs-calc-total">$0</div>
        <div class="gs-calc__breakdown" id="gs-calc-breakdown"></div>
        <a href="#" id="gs-calc-quote-link" class="gs-calc__cta">
            Get Detailed Quote &rarr;
        </a>
        <p class="gs-calc__note">Price includes GST. Delivery and custom options available.</p>
    </div>
</div>
