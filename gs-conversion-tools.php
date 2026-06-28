<?php
/**
 * Plugin Name: GS Conversion Tools
 * Plugin URI:  https://guerillasteel.com.au
 * Description: Quiz, price calculator, quote form prefill, and social proof tools for Guerilla Steel Stables.
 * Version:     2.0.0
 * Author:      Brighter Websites
 * Author URI:  https://brighterwebsites.com.au
 * Text Domain: gs-conversion-tools
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GS_CT_VERSION', '2.0.0' );
define( 'GS_CT_DIR',     plugin_dir_path( __FILE__ ) );
define( 'GS_CT_URL',     plugin_dir_url( __FILE__ ) );

require_once GS_CT_DIR . 'includes/class-gs-pricing.php';
require_once GS_CT_DIR . 'includes/class-gs-calculator.php';
require_once GS_CT_DIR . 'includes/class-gs-quiz.php';
require_once GS_CT_DIR . 'includes/class-gs-prefill.php';
require_once GS_CT_DIR . 'includes/class-gs-social-proof.php';

GS_Pricing::init();
GS_Calculator::init();
GS_Quiz::init();
GS_Prefill::init();
GS_Social_Proof::init();
