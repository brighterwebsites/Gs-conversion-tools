<?php
/**
 * Central pricing configuration.
 * Injects window.GS_PRICING_CONFIG for use by all JS tools.
 */
class GS_Pricing {

    public static function init() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_config' ], 5 );
    }

    /**
     * Everything the front-end tools need, and nothing else.
     *
     * This array is published to the browser as window.GS_PRICING_CONFIG, so
     * anything added here is public. Only 'base' and 'upgrades' are read by the
     * calculator and quiz; the retrofit, panels, roofExtension, tackRooms,
     * gstRate, currency, business and notes blocks were dropped because nothing
     * consumed them -- see git history if any are needed again.
     *
     * When the WooCommerce rebuild lands, this is the single place to change:
     * return prices from the products instead of these literals and the
     * calculator and quiz JS need no changes at all.
     */
    public static function get_config() {
        return [
            'base' => [
                '4x4' => [
                    'label'   => '4m × 4m Standard',
                    'first'   => 4500,
                    'extra'   => 4000,
                    'install' => 550,
                    'front'   => 4,
                    'depth'   => 4,
                ],
                '5x4' => [
                    'label'   => '5m × 4m Large',
                    'first'   => 6100,
                    'extra'   => 5900,
                    'install' => 700,
                    'front'   => 5,
                    'depth'   => 4,
                ],
                '4x5' => [
                    'label'   => '4m × 5m Large',
                    'first'   => 6100,
                    'extra'   => 5900,
                    'install' => 700,
                    'front'   => 4,
                    'depth'   => 5,
                ],
                '5x5' => [
                    'label'   => '5m × 5m XLarge',
                    'first'   => 7800,
                    'extra'   => 6200,
                    'install' => 850,
                    'front'   => 5,
                    'depth'   => 5,
                ],
            ],
            'upgrades' => [
                'pitchRoof' => 450,
                'yokeGates' => 750,
                'anchors'   => 100,
            ],
        ];
    }

    /**
     * Register the pricing config as a dependency-only handle.
     *
     * The calculator and quiz both declare 'gs-pricing-config' as a dependency,
     * so WordPress pulls it in automatically on pages that use those tools --
     * and only those pages. It used to be enqueued unconditionally, which
     * printed the entire price list, including retrofit panel pricing, into the
     * source of every page on the site.
     *
     * Filter 'gs_pricing_config_always' to true if something outside this plugin
     * needs window.GS_PRICING_CONFIG on pages with no shortcode.
     */
    public static function register_config() {
        wp_register_script( 'gs-pricing-config', false, [], GS_CT_VERSION, true );
        wp_add_inline_script(
            'gs-pricing-config',
            'window.GS_PRICING_CONFIG = ' . wp_json_encode( self::get_config() ) . ';'
        );

        if ( apply_filters( 'gs_pricing_config_always', false ) ) {
            wp_enqueue_script( 'gs-pricing-config' );
        }
    }

    // PHP helpers (for use in templates/other PHP code)

    public static function calculate_base_price( $size, $bays ) {
        $config = self::get_config();
        if ( ! isset( $config['base'][ $size ] ) ) {
            return 0;
        }
        $s = $config['base'][ $size ];
        return $s['first'] + ( $s['extra'] * max( 0, $bays - 1 ) );
    }

    public static function format_price( $amount, $show_gst_note = false ) {
        $formatted = '$' . number_format( $amount, 0, '.', ',' );
        if ( $show_gst_note ) {
            $formatted .= ' (inc GST)';
        }
        return $formatted;
    }
}
