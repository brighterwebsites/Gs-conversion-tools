<?php
/**
 * Cached aggregate counts behind the store trust-signal shortcodes.
 *
 * These are approximate by design — "40 stables delivered" does not need to be
 * correct to the second. Every figure is cached in a transient and invalidated
 * on the events that would move it, so a page render never pays for the
 * aggregate query.
 *
 * NOTE: this is the only part of GS Conversion Tools that writes to the
 * database. The counters in class-gs-social-proof.php are still calculated on
 * the fly and store nothing. Transient keys are listed in $keys below and all
 * start with gs_stats_.
 *
 * Scope: published, in-stock products only. Out-of-stock products are excluded
 * from the product count.
 */
class GS_Stats {

    const CACHE_TTL    = 6 * HOUR_IN_SECONDS;
    const CACHE_PREFIX = 'gs_stats_';

    /** @var string[] Every transient key this class owns, for bulk flushing. */
    private static $keys = [
        'products',
        'orders',
        'customers',
    ];

    public static function init() {
        // Stock movement.
        add_action( 'woocommerce_product_set_stock',        [ __CLASS__, 'flush' ] );
        add_action( 'woocommerce_variation_set_stock',      [ __CLASS__, 'flush' ] );
        add_action( 'woocommerce_product_set_stock_status', [ __CLASS__, 'flush' ] );

        // Catalog changes.
        add_action( 'save_post_product', [ __CLASS__, 'flush' ] );
        add_action( 'deleted_post',      [ __CLASS__, 'flush' ] );
        add_action( 'trashed_post',      [ __CLASS__, 'flush' ] );

        // Orders and customers.
        add_action( 'woocommerce_new_order',            [ __CLASS__, 'flush' ] );
        add_action( 'woocommerce_order_status_changed', [ __CLASS__, 'flush' ] );
        add_action( 'user_register',                    [ __CLASS__, 'flush' ] );
        add_action( 'deleted_user',                     [ __CLASS__, 'flush' ] );
    }

    /**
     * Whether the counts can be answered at all. The product query reads
     * WooCommerce's own lookup table, which does not exist without it.
     *
     * @return bool
     */
    public static function available() {
        return class_exists( 'WooCommerce' );
    }

    public static function flush() {
        foreach ( self::$keys as $key ) {
            delete_transient( self::CACHE_PREFIX . $key );
        }
    }

    /**
     * @param string   $key
     * @param callable $callback
     * @return int
     */
    private static function cached( $key, callable $callback ) {
        $transient = self::CACHE_PREFIX . $key;
        $cached    = get_transient( $transient );

        if ( false !== $cached ) {
            return (int) $cached;
        }

        $value = (int) call_user_func( $callback );
        set_transient( $transient, $value, self::CACHE_TTL );

        return $value;
    }

    /**
     * Published, in-stock products.
     *
     * @return int
     */
    public static function product_count() {
        if ( ! self::available() ) {
            return 0;
        }

        return self::cached( 'products', function () {
            global $wpdb;

            // No caller input reaches this query; the only variables are
            // $wpdb's own table names, which cannot be prepared.
            $sql = "SELECT COUNT(*)
                FROM {$wpdb->prefix}wc_product_meta_lookup AS lookup
                INNER JOIN {$wpdb->posts} AS posts
                    ON posts.ID = lookup.product_id
                WHERE posts.post_type = 'product'
                    AND posts.post_status = 'publish'
                    AND lookup.stock_status = 'instock'";

            return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB
        } );
    }

    /**
     * Completed and processing orders. wc_orders_count() is used because it is
     * HPOS-aware — a direct posts query would return zero on a store using the
     * wc_orders tables.
     *
     * @return int
     */
    public static function order_count() {
        if ( ! self::available() ) {
            return 0;
        }

        return self::cached( 'orders', function () {
            if ( ! function_exists( 'wc_orders_count' ) ) {
                return 0;
            }

            return (int) wc_orders_count( 'completed' ) + (int) wc_orders_count( 'processing' );
        } );
    }

    /**
     * Registered customer accounts. Note this undercounts real purchasers,
     * because guest checkouts never create a user — order_count() is the
     * better trust signal for that reason.
     *
     * @return int
     */
    public static function customer_count() {
        if ( ! self::available() ) {
            return 0;
        }

        return self::cached( 'customers', function () {
            $query = new WP_User_Query( [
                'role'        => 'customer',
                'number'      => 1,
                'fields'      => 'ID',
                'count_total' => true,
            ] );

            return (int) $query->get_total();
        } );
    }
}
