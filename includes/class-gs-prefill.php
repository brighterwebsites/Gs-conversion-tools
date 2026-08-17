<?php
/**
 * Quote form prefill.
 *
 * Enqueues the prefill script on any page that has source=quiz or source=calculator
 * in the URL. This prevents the script from firing on WooCommerce or other pages
 * that happen to carry unrelated query parameters.
 *
 * To load on a specific page unconditionally, add the body class gs-prefill-page
 * via your page builder, or use the gs_prefill_enabled filter.
 */
class GS_Prefill {

    public static function init() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'maybe_enqueue' ], 20 );
    }

    public static function maybe_enqueue() {
        $source = isset( $_GET['source'] ) ? sanitize_key( $_GET['source'] ) : '';

        $enabled = in_array( $source, [ 'quiz', 'calculator' ], true )
            || apply_filters( 'gs_prefill_enabled', false );

        if ( ! $enabled ) {
            return;
        }

        wp_enqueue_style(
            'gs-tools',
            GS_CT_URL . 'assets/css/gs-tools.css',
            [],
            GS_CT_VERSION
        );

        wp_enqueue_script(
            'gs-prefill',
            GS_CT_URL . 'assets/js/gs-prefill.js',
            [],
            GS_CT_VERSION,
            true
        );
    }
}
