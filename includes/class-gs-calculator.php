<?php
/**
 * Simple stable price calculator.
 * Shortcode: [gs_stable_calc]
 *
 * Attributes:
 *   default_size  - 4x4 | 5x4 | 4x5 | 5x5
 *   default_bays  - 1–5
 *   show_title    - yes | no
 *   quote_url     - URL path to quote form (default /stable-quote/)
 */
class GS_Calculator {

    public static function init() {
        add_shortcode( 'gs_stable_calc', [ __CLASS__, 'render' ] );
        // Keep legacy shortcode name working
        add_shortcode( 'simple_stable_calc', [ __CLASS__, 'render' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
    }

    public static function register_assets() {
        wp_register_script(
            'gs-calculator',
            GS_CT_URL . 'assets/js/gs-calculator.js',
            [ 'gs-pricing-config' ],
            GS_CT_VERSION,
            true
        );
        wp_register_style(
            'gs-tools',
            GS_CT_URL . 'assets/css/gs-tools.css',
            [],
            GS_CT_VERSION
        );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( [
            'default_size' => '4x4',
            'default_bays' => '1',
            'show_title'   => 'no',
            'quote_url'    => '/stable-quote/',
        ], $atts );

        wp_enqueue_script( 'gs-calculator' );
        wp_enqueue_style( 'gs-tools' );

        ob_start();
        include GS_CT_DIR . 'templates/calculator.php';
        return ob_get_clean();
    }
}
