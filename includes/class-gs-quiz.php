<?php
/**
 * Stable finder quiz — rendered inline via shortcode (no iframe).
 * Shortcode: [gs_quiz]
 *
 * Attributes:
 *   quote_url     - URL path to quote form (default /stables/quote/)
 *   learn_more_url - Base URL for learn-more links (default /stables-base-model-compare)
 */
class GS_Quiz {

    public static function init() {
        add_shortcode( 'gs_quiz', [ __CLASS__, 'render' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
    }

    public static function register_assets() {
        wp_register_script(
            'gs-quiz',
            GS_CT_URL . 'assets/js/gs-quiz.js',
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
            'quote_url'      => '/stables/quote/',
            'learn_more_url' => '/stables-base-model-compare',
        ], $atts );

        wp_enqueue_script( 'gs-quiz' );
        wp_enqueue_style( 'gs-tools' );

        // Pass config to JS
        wp_add_inline_script(
            'gs-quiz',
            'window.GS_QUIZ_CONFIG = ' . wp_json_encode( [
                'quoteUrl'     => esc_url( $atts['quote_url'] ),
                'learnMoreUrl' => esc_url( $atts['learn_more_url'] ),
            ] ) . ';',
            'before'
        );

        ob_start();
        include GS_CT_DIR . 'templates/quiz.php';
        return ob_get_clean();
    }
}
