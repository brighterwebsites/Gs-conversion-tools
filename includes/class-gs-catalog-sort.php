<?php
/**
 * Adds A-Z and Z-A to the shop page sort dropdown.
 *
 * Three hooks are needed and it is easy to wire only the first two: the
 * options filter puts the entries in the dropdown, the ordering-args filter
 * makes them actually sort, and the default-options filter makes them
 * selectable as the store's default in the Customizer.
 */
class GS_Catalog_Sort {

    public static function init() {
        add_filter( 'woocommerce_catalog_orderby',                 [ __CLASS__, 'add_options' ] );
        add_filter( 'woocommerce_default_catalog_orderby_options', [ __CLASS__, 'add_options' ] );
        add_filter( 'woocommerce_get_catalog_ordering_args',       [ __CLASS__, 'ordering_args' ], 10, 3 );
    }

    /**
     * @param array $options
     * @return array
     */
    public static function add_options( $options ) {
        $options['title_asc']  = __( 'Sort by name: A to Z', 'gs-conversion-tools' );
        $options['title_desc'] = __( 'Sort by name: Z to A', 'gs-conversion-tools' );

        return $options;
    }

    /**
     * @param array  $args
     * @param string $orderby
     * @param string $order
     * @return array
     */
    public static function ordering_args( $args, $orderby = '', $order = '' ) {
        // WooCommerce passes the resolved orderby from WC 3.x onward, but
        // falls back to the raw request on some paths; take whichever is set
        // and match it against our own two values only.
        $requested = $orderby;

        if ( '' === $requested && isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $requested = sanitize_text_field( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        if ( 'title_asc' === $requested ) {
            $args['orderby']  = 'title';
            $args['order']    = 'ASC';
            $args['meta_key'] = ''; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        }

        if ( 'title_desc' === $requested ) {
            $args['orderby']  = 'title';
            $args['order']    = 'DESC';
            $args['meta_key'] = ''; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        }

        return $args;
    }
}
