<?php
/**
 * WooCommerce storefront shortcodes.
 *
 * [gs_prod_count]         – Published in-stock product count
 * [gs_order_count]        – Completed + processing orders
 * [gs_customer_count]     – Registered customer accounts
 * [gs_product_categories] – Linked <div>s per category, zero-count skipped
 * [gs_first_product_cat]  – The first product category of the current product
 *
 * Shortcode attributes are author-supplied input, so every attribute that
 * reaches markup is escaped at the point of output, and every attribute that
 * selects behaviour is matched against an allowlist rather than passed
 * through. Counts are integers from GS_Stats and are escaped regardless.
 *
 * Registered whether or not WooCommerce is active: without it the counts
 * return an empty string rather than leaving the raw tag printed in the page.
 */
class GS_Store_Shortcodes {

    public static function init() {
        add_shortcode( 'gs_prod_count',         [ __CLASS__, 'prod_count' ] );
        add_shortcode( 'gs_order_count',        [ __CLASS__, 'order_count' ] );
        add_shortcode( 'gs_customer_count',     [ __CLASS__, 'customer_count' ] );
        add_shortcode( 'gs_product_categories', [ __CLASS__, 'product_categories' ] );
        add_shortcode( 'gs_first_product_cat',  [ __CLASS__, 'first_product_cat' ] );
    }

    /**
     * Rounds down to a "friendly" figure for trust-signal copy, so the number
     * reads as a claim rather than a live meter: 3247 -> "3,200+".
     *
     * A value sitting exactly on the step keeps the "+" (40 with round="10"
     * reads "40+"), matching CNS Site Functions, which this was ported from.
     * Values at or below the step are left alone rather than floored to zero.
     *
     * @param int    $value
     * @param string $round 'none' or a power-of-ten step as a string.
     * @return string
     */
    private static function format( $value, $round = 'none' ) {
        $steps = [
            '10'   => 10,
            '50'   => 50,
            '100'  => 100,
            '1000' => 1000,
        ];

        $suffix = '';

        if ( isset( $steps[ $round ] ) && $value > $steps[ $round ] ) {
            $value  = (int) ( floor( $value / $steps[ $round ] ) * $steps[ $round ] );
            $suffix = '+';
        }

        return number_format_i18n( $value ) . $suffix;
    }

    /**
     * @param array    $atts
     * @param callable $count
     * @return string
     */
    private static function render_count( $atts, callable $count ) {
        if ( ! GS_Stats::available() ) {
            return '';
        }

        $a = shortcode_atts( [ 'round' => 'none' ], $atts, 'gs_count' );

        return esc_html( self::format( (int) call_user_func( $count ), $a['round'] ) );
    }

    public static function prod_count( $atts ) {
        return self::render_count( $atts, [ 'GS_Stats', 'product_count' ] );
    }

    public static function order_count( $atts ) {
        return self::render_count( $atts, [ 'GS_Stats', 'order_count' ] );
    }

    public static function customer_count( $atts ) {
        return self::render_count( $atts, [ 'GS_Stats', 'customer_count' ] );
    }

    /**
     * The first product category of a single product.
     *
     * "First" is GS_Product_Cat's answer — WooCommerce's own term order, which
     * respects manual ordering on the category screen and is not alphabetical
     * unless you pass orderby="name". The add-to-cart button text uses the
     * same call, so the two always name the same category.
     *
     * Returns the fallback (empty by default) anywhere that is not a product:
     * archives, pages, the blog. Safe to leave in a shared template.
     *
     * @param array $atts
     * @return string
     */
    public static function first_product_cat( $atts ) {
        $a = shortcode_atts( [
            'id'       => '',
            'field'    => 'name',
            'link'     => 'no',
            'class'    => '',
            'exclude'  => '',
            'fallback' => '',
            'orderby'  => 'default',
        ], $atts, 'gs_first_product_cat' );

        $fallback = esc_html( $a['fallback'] );

        $product_id = ( '' !== $a['id'] ) ? absint( $a['id'] ) : (int) get_the_ID();

        $term = GS_Product_Cat::first_term( $product_id, $a['orderby'], $a['exclude'] );

        if ( ! $term ) {
            return $fallback;
        }

        $field = in_array( $a['field'], [ 'name', 'slug', 'id', 'url' ], true ) ? $a['field'] : 'name';

        if ( 'id' === $field ) {
            return (string) (int) $term->term_id;
        }

        $url = get_term_link( $term );
        $url = is_wp_error( $url ) ? '' : $url;

        if ( 'url' === $field ) {
            return esc_url( $url );
        }

        $label = ( 'slug' === $field ) ? $term->slug : $term->name;

        if ( 'yes' !== strtolower( $a['link'] ) || '' === $url ) {
            return esc_html( $label );
        }

        if ( '' === $a['class'] ) {
            return sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url ), esc_html( $label ) );
        }

        return sprintf(
            '<a href="%1$s" class="%2$s">%3$s</a>',
            esc_url( $url ),
            esc_attr( $a['class'] ),
            esc_html( $label )
        );
    }

    /**
     * Product categories as linked divs (not a list), skipping any category
     * with a product count of zero.
     *
     * @param array $atts
     * @return string
     */
    public static function product_categories( $atts ) {
        $a = shortcode_atts( [
            'class'      => 'gs-category',
            'wrap_class' => 'gs-category-list',
            'orderby'    => 'name',
            'order'      => 'ASC',
            'parent'     => '',
            'show_count' => 'no',
            'limit'      => '0',
        ], $atts, 'gs_product_categories' );

        if ( ! taxonomy_exists( 'product_cat' ) ) {
            return '';
        }

        $args = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'orderby'    => in_array( $a['orderby'], [ 'name', 'count', 'slug', 'menu_order' ], true ) ? $a['orderby'] : 'name',
            'order'      => ( 'DESC' === strtoupper( $a['order'] ) ) ? 'DESC' : 'ASC',
        ];

        if ( '' !== $a['parent'] ) {
            $args['parent'] = absint( $a['parent'] );
        }

        $limit = absint( $a['limit'] );
        if ( $limit > 0 ) {
            $args['number'] = $limit;
        }

        $terms = get_terms( $args );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return '';
        }

        $show_count = ( 'yes' === strtolower( $a['show_count'] ) );

        $out = sprintf( '<div class="%s">', esc_attr( $a['wrap_class'] ) );

        foreach ( $terms as $term ) {
            // hide_empty is honoured by get_terms, but a category whose only
            // products are out of stock still reports a non-zero count, so
            // check explicitly rather than trusting the flag alone.
            if ( empty( $term->count ) ) {
                continue;
            }

            $link = get_term_link( $term );
            if ( is_wp_error( $link ) ) {
                continue;
            }

            $label = $term->name;
            if ( $show_count ) {
                $label .= ' (' . number_format_i18n( $term->count ) . ')';
            }

            $out .= sprintf(
                '<div class="%1$s"><a href="%2$s">%3$s</a></div>',
                esc_attr( $a['class'] ),
                esc_url( $link ),
                esc_html( $label )
            );
        }

        $out .= '</div>';

        return $out;
    }
}
