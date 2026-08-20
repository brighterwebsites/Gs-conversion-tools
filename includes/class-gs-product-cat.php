<?php
/**
 * The one place that answers "what is this product's category?".
 *
 * Both [gs_first_product_cat] and the add-to-cart button text need the same
 * answer, and they have to agree — a button reading "Order this Stable" above
 * a badge reading "Yards" is worse than either alone. So the rule lives here
 * and both callers ask this class.
 *
 * "First" means the first term WooCommerce itself returns, which respects the
 * manual term ordering set on the category screen — the same order shown in
 * the product editor. It is NOT alphabetical unless you ask for that.
 */
class GS_Product_Cat {

    /**
     * @param int    $product_id
     * @param string $orderby 'name' to sort alphabetically, anything else for
     *                        WooCommerce's own order.
     * @param string $exclude Comma separated category slugs or IDs to skip.
     * @return WP_Term|null
     */
    public static function first_term( $product_id, $orderby = 'default', $exclude = '' ) {
        $product_id = (int) $product_id;

        if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
            return null;
        }

        $args = [];

        if ( 'name' === $orderby ) {
            $args['orderby'] = 'name';
        }

        // wc_get_product_terms honours manual term ordering; wp_get_post_terms
        // does not, so prefer it and only fall back if WooCommerce is absent.
        if ( function_exists( 'wc_get_product_terms' ) ) {
            $terms = wc_get_product_terms( $product_id, 'product_cat', $args );
        } else {
            $terms = wp_get_post_terms( $product_id, 'product_cat', $args );
        }

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return null;
        }

        $skip = array_filter( array_map( 'trim', explode( ',', (string) $exclude ) ) );
        $skip = array_map( 'strtolower', $skip );

        foreach ( $terms as $term ) {
            if ( ! is_object( $term ) || empty( $term->term_id ) ) {
                continue;
            }

            if ( in_array( strtolower( $term->slug ), $skip, true )
                || in_array( (string) $term->term_id, $skip, true )
            ) {
                continue;
            }

            return $term;
        }

        return null;
    }

    /**
     * The first category's name, or '' when the product has none.
     *
     * @param int $product_id
     * @return string
     */
    public static function first_name( $product_id ) {
        $term = self::first_term( $product_id );

        return $term ? $term->name : '';
    }
}
