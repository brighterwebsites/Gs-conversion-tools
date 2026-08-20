<?php
/**
 * Category-aware add-to-cart button text.
 *
 * Shop / archive loops:  "Order this Stable"
 * Single product page:   "Get this Stable"
 *
 * The category comes from GS_Product_Cat, the same call [gs_first_product_cat]
 * makes, so the button and the badge can never name different categories. A
 * product in more than one category always uses the first; a product in none
 * falls back to "item" — "Order this item".
 *
 * Only purchasable, in-stock products are relabelled. WooCommerce says
 * "Read more" for a product that cannot be bought and hides the form when it
 * is out of stock, and "Order this Stable" over a button that does neither
 * would be a lie. External and grouped products report themselves as not
 * purchasable, so their own button text is left alone too.
 */
class GS_Cart_Button {

    public static function init() {
        add_filter( 'woocommerce_product_add_to_cart_text',        [ __CLASS__, 'loop_text' ], 10, 2 );
        add_filter( 'woocommerce_product_single_add_to_cart_text', [ __CLASS__, 'single_text' ], 10, 2 );
    }

    /**
     * @param string     $text
     * @param WC_Product $product
     * @return string
     */
    public static function loop_text( $text, $product = null ) {
        /* translators: %s: product category name, or "item" when the product has none. */
        $template = __( 'Order this %s', 'gs-conversion-tools' );

        return self::apply( $text, $product, 'loop', apply_filters( 'gs_add_to_cart_loop_template', $template ) );
    }

    /**
     * @param string     $text
     * @param WC_Product $product
     * @return string
     */
    public static function single_text( $text, $product = null ) {
        /* translators: %s: product category name, or "item" when the product has none. */
        $template = __( 'Get this %s', 'gs-conversion-tools' );

        return self::apply( $text, $product, 'single', apply_filters( 'gs_add_to_cart_single_template', $template ) );
    }

    /**
     * @param string     $text     WooCommerce's own button text.
     * @param WC_Product $product
     * @param string     $context  'loop' or 'single'.
     * @param string     $template sprintf template taking the category name.
     * @return string
     */
    private static function apply( $text, $product, $context, $template ) {
        if ( ! self::should_relabel( $product, $context ) ) {
            return $text;
        }

        $term = GS_Product_Cat::first_name( $product->get_id() );

        if ( '' === $term ) {
            $term = apply_filters( 'gs_add_to_cart_fallback_term', __( 'item', 'gs-conversion-tools' ), $product );
        }

        return sprintf( $template, $term );
    }

    /**
     * @param mixed  $product
     * @param string $context
     * @return bool
     */
    private static function should_relabel( $product, $context ) {
        if ( ! $product instanceof WC_Product ) {
            return false;
        }

        $relabel = $product->is_purchasable() && $product->is_in_stock();

        /**
         * Filter whether this product's button gets category-aware text.
         *
         * Return false to keep WooCommerce's own wording — useful for a
         * product type where "Select options" or "Buy product" says something
         * the replacement does not.
         *
         * @param bool       $relabel
         * @param WC_Product $product
         * @param string     $context 'loop' or 'single'.
         */
        return (bool) apply_filters( 'gs_add_to_cart_relabel', $relabel, $product, $context );
    }
}
