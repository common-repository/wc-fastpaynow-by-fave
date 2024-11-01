<?php
if ( !defined( 'ABSPATH' ) ) exit;

class FastPayNow_By_Fave_WC {

    // Load dependencies
    public function __construct() {

        // Functions
        require_once( FASTPAYNOW_BY_FAVE_WC_PATH . '/includes/functions.php' );

        // Logger
        require_once( FASTPAYNOW_BY_FAVE_WC_PATH . '/includes/traits/trait-fastpay-wc-logger.php' );

        // Utils
        require_once( FASTPAYNOW_BY_FAVE_WC_PATH . '/includes/utils.php' );

        // API
        require_once( FASTPAYNOW_BY_FAVE_WC_PATH . '/includes/abstracts/abstract-fastpay-wc-client.php' );
        require_once( FASTPAYNOW_BY_FAVE_WC_PATH . '/includes/class-fastpay-wc-api.php' );

        // Admin
        require_once( FASTPAYNOW_BY_FAVE_WC_PATH . '/admin/class-fastpay-wc-admin.php' );

        // Initialize payment gateway
        require_once( FASTPAYNOW_BY_FAVE_WC_PATH . '/includes/class-fastpay-wc-init.php' );

        // Frontend
        require_once( FASTPAYNOW_BY_FAVE_WC_PATH . '/public/class-fastpay-wc-public.php' );

        // WC API routes
        require_once( FASTPAYNOW_BY_FAVE_WC_PATH . '/includes/class-fastpay-wc-routes.php' );

        // Button Manager
        require_once FASTPAYNOW_BY_FAVE_WC_PLUGIN_DIR . '/public/class-fastpay-for-woocommerce-button-manager.php';
    }

}
new FastPayNow_By_Fave_WC();
