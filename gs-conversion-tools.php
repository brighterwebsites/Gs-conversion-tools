<?php
/**
 * Plugin Name: GS Conversion Tools
 * Plugin URI:  https://guerillasteel.com.au
 * Description: Quiz, price calculator, quote form prefill, social proof tools, and WooCommerce storefront extras (count shortcodes, A-Z catalog sorting, category-aware add-to-cart text) for Guerilla Steel Stables.
 * Version:     2.1.0
 * Author:      Brighter Websites
 * Author URI:  https://brighterwebsites.com.au
 * Text Domain: gs-conversion-tools
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GS_CT_VERSION', '2.1.0' );
define( 'GS_CT_DIR',     plugin_dir_path( __FILE__ ) );
define( 'GS_CT_URL',     plugin_dir_url( __FILE__ ) );

require_once GS_CT_DIR . 'includes/class-gs-pricing.php';
require_once GS_CT_DIR . 'includes/class-gs-calculator.php';
require_once GS_CT_DIR . 'includes/class-gs-quiz.php';
require_once GS_CT_DIR . 'includes/class-gs-prefill.php';
require_once GS_CT_DIR . 'includes/class-gs-social-proof.php';
require_once GS_CT_DIR . 'includes/class-gs-product-cat.php';
require_once GS_CT_DIR . 'includes/class-gs-stats.php';
require_once GS_CT_DIR . 'includes/class-gs-store-shortcodes.php';
require_once GS_CT_DIR . 'includes/class-gs-catalog-sort.php';
require_once GS_CT_DIR . 'includes/class-gs-cart-button.php';

GS_Pricing::init();
GS_Calculator::init();
GS_Quiz::init();
GS_Prefill::init();
GS_Social_Proof::init();

// Registered unconditionally so the tags never print as literal text: without
// WooCommerce the counts return an empty string instead.
GS_Store_Shortcodes::init();

add_action( 'plugins_loaded', 'gs_ct_init_store' );

/**
 * The WooCommerce integrations.
 *
 * Checked on plugins_loaded rather than here, because plugin files are
 * included in alphabetical order and WooCommerce may not be in memory yet at
 * the point this file runs.
 */
function gs_ct_init_store() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    GS_Stats::init();
    GS_Catalog_Sort::init();
    GS_Cart_Button::init();
}
