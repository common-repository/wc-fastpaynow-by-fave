<?php
if ( !defined( 'ABSPATH' ) ) exit;

class FastPayNow_By_WC_Init {

    private $gateway_class = 'FastPayNow_By_Fave_WC_Gateway';

    // Register hooks
    public function __construct() {

        add_action( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
        add_action( 'init', array( $this, 'load_dependencies' ) );

    }

    // Register FastPay as WooCommerce payment method
    public function register_gateway( $methods ) {
        $methods[] = $this->gateway_class;
        return $methods;
    }

    // Load required files
    public function load_dependencies() {

        if ( !class_exists( 'WC_Payment_Gateway' ) ) {
            return;
        }

        require_once( FASTPAYNOW_BY_FAVE_WC_PATH . '/admin/settings.php' );
        require_once( FASTPAYNOW_BY_FAVE_WC_PATH . '/includes/class-fastpay-wc-gateway.php' );

        $this->register_hooks();

    }

    // Register gateway global hooks (outside of the class)
    private function register_hooks() {

        add_action( 'fastpay_wc_check_transaction_status', array( $this->gateway_class, 'check_transaction_status' ) );
        add_action( 'woocommerce_cart_is_empty', array( $this->gateway_class, 'cancel_order_page' ), 20 );

        add_filter( 'woocommerce_cart_is_empty', array( $this->gateway_class, 'cancel_order_page_remove_wc_content' ), 1 );
        add_filter( 'woocommerce_get_shop_page_id', array( $this->gateway_class, 'cancel_order_page_remove_wc_button' ) );

        $this->button_manager = FastpayNow_By_Fave_For_Woocommerce_Button_Manager::instance();
    }

}
new FastPayNow_By_WC_Init();
