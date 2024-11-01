<?php
/**
 * Plugin Name: FastPayNow By Fave
 * Description: FastPayNow integration for WooCommerce.
 * Text Domain: wc-fastpaynow-by-fave
 * Version:     1.0.1
 * Author:      Fave Asia Sdn Bhd
 * Author URI:  https://myfave.com/
 * License:     GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( !defined( 'ABSPATH' ) ) exit;

// https://wordpress.stackexchange.com/questions/89456/checking-for-existence-of-constants-before-defining-them
if (!defined('FASTPAYNOW_BY_FAVE_WC_FILE')) {
    define( 'FASTPAYNOW_BY_FAVE_WC_FILE', __FILE__ );
}
if (!defined('FASTPAYNOW_BY_FAVE_WC_PLUGIN_DIR')) {
    define( 'FASTPAYNOW_BY_FAVE_WC_PLUGIN_DIR', dirname( __FILE__ ) );
}
if (!defined('FASTPAYNOW_BY_FAVE_WC_URL')) {
    define( 'FASTPAYNOW_BY_FAVE_WC_URL', plugin_dir_url( __FILE__ ) );
}
if (!defined('FASTPAYNOW_BY_FAVE_WC_PATH')) {
    define( 'FASTPAYNOW_BY_FAVE_WC_PATH', untrailingslashit(plugin_dir_path( __FILE__ )) );
}
if (!defined('FASTPAYNOW_BY_FAVE_WC_BASENAME')) {
    define( 'FASTPAYNOW_BY_FAVE_WC_BASENAME', plugin_basename( FASTPAYNOW_BY_FAVE_WC_FILE ) );
}

define( 'FASTPAYNOW_BY_FAVE_WC_VERSION', '1.0.1' );
define( 'FASTPAYNOW_BY_FAVE_WC_STAGING', "no" );

// Run during plugin activation
function activate_fastpay_wc() {
    require_once( FASTPAYNOW_BY_FAVE_WC_PATH . '/includes/class-fastpay-wc-activator.php' );
    FastPayNow_By_Fave_WC_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_fastpay_wc' );

// Plugin core class
require( FASTPAYNOW_BY_FAVE_WC_PATH . '/includes/class-fastpay-wc.php' );
