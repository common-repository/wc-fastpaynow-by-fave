<?php
if ( !defined( 'ABSPATH' ) ) exit;

class FastPayNow_By_Fave_WC_Gateway extends WC_Payment_Gateway {

    use FastPayNow_By_Fave_WC_Logger;

    private $fastpay;

    private $country;
    private $app_id;

    private $api_key;
    private $outlet_id;
    private $sandbox;
    private $debug;

    public function __construct() {
        $this->id                 = 'fastpaynow_by_fave';
        $this->has_fields         = true;
        $this->method_title       = __( 'FastPayNow By Fave', 'wc-fastpaynow-by-fave' );
        $this->method_description = __( 'Pay with your preferred credit card, eWallet, online banking.', 'wc-fastpaynow-by-fave' );
        $this->order_button_text  = __( 'Pay with FastPayNow', 'wc-fastpaynow-by-fave' );
        $this->supports           = array( 'products', 'refunds' );

        $this->init_form_fields();
        $this->init_settings();

        $this->country            = wc_get_country_fpn_by_fave();
        $this->app_id             = wc_get_app_id_fpn_by_fave();

        $cashback_rate = $this->get_option( 'cashback_rate', 0 ) ? sprintf( __( ' – %s%% Cashback', 'wc-fastpaynow-by-fave' ), $this->get_option( 'cashback_rate' ) ) : '';

        $this->title              = $this->get_option( 'title' ); // Payment method name that will be shown in order page (Default to Fastpay)
        if ( empty($this-> title) ) {
            $this->title = 'FastPay';
        }

        $this->icon               = FASTPAYNOW_BY_FAVE_WC_URL . 'assets/images/PurpleLightningIcon.svg';

        if ( $description = $this->get_option( 'description' ) ) {
            $this->description = $description;
        } else {
            $this->description = wc_get_description_fpn_by_fave();
        }

        $this->api_key            = $this->get_option( 'api_key' );
        $this->outlet_id          = $this->get_option( 'outlet_id' );
        $this->sandbox            = $this->get_option( 'sandbox' ) === 'yes' ? true : false;
        $this->debug              = $this->get_option( 'debug' ) === 'yes' ? true : false;

        $this->register_hooks();

        // Check if the payment gateway is ready to use
        if ( !$this->validate_required_settings() ) {
            $this->enabled = 'no';
        }

        $this->init_api();

    }

    // Register WooCommerce payment gateway hooks
    private function register_hooks() {

        do_action('display_fastpay_button_checkout_page');
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_' . $this->id . '_wc_gateway', array( $this, 'handle_ipn_callback' ) );
        add_action( 'woocommerce_before_thankyou', array( $this, 'on_thankyou_page_load' ) );

    }

    // Check if all required settings is filled
    private function validate_required_settings() {
        return $this->country
            && $this->app_id
            && $this->api_key
            && $this->outlet_id;
    }

    // Form fields
    public function init_form_fields() {
        $this->form_fields = apply_filters( 'fastpay_wc_form_fields', fastpaynow_by_fave_wc_settings_form_fields() );
    }

    // Initialize API
    private function init_api() {
        $this->fastpay = new FastPayNow_By_Fave_WC_API( $this->api_key, $this->country, $this->sandbox );
    }

    // Process the payment
    public function process_payment( $order_id ) {
        $source = get_class($this) . '|' . __FUNCTION__;

        // If order is new, then create transient
        if ( get_post_meta( $order_id, '_fastpay_wc_is_new_order', true ) == '') {
            update_post_meta( $order_id, '_fastpay_wc_is_new_order', $order_id );
        }else{
            // Otherwise, append A
            wc_update_omni_reference_suffix_fpn_by_fave( $order_id );
        }

        if ( !$this->validate_required_settings() ) {
            $this->log( $source, 'Configuration is not set up properly.' );
            return false;
        }

        if ( !$order = wc_get_order( $order_id ) ) {
            $this->log( $source, "Order " . $order_id . " doesn't exists." );
            return false;
        }

        // If payment URL has been generated before, redirect customer to the payment page
        if ( $payment_url = get_transient( 'fastpay_wc_order_payment_url_' . $order_id ) ) {
            return array(
                'result'   => 'success',
                'redirect' => $payment_url,
            );
        }

        $this->log( $source, 'Generating QR code for order #' . $order_id );

        $params = $this->get_express_qr_code_params( $order );
        $this->log( $source, 'QR code Parameters' . json_encode($params) );
        $api_key = wc_get_setting_fpn_by_fave('api_key');
        $this->log( $source, 'API Key' . $api_key );
        $signature = FastPayNow_By_Fave_Utils::generate_json_sign($params, $api_key);
        $params["sign"] = $signature;

        $this->init_api();

        list( $code, $response ) = $this->fastpay->generate_fastpay_link( $params );

        if(isset( $response['error'] )){
            $this->log( $source, 'Order ' . $order_id . ' have error in generate fastpay link. Error: '. $response['error'] );
            wc_add_notice( esc_html__( 'Payment error: ', 'wc-fastpaynow-by-fave' ) . $response['error_description'], 'error' );
            exit();
        }

        $expires = isset( $response['expires_in'] ) ? $response['expires_in'] : 360;

        // Check transaction status after QR code expires
        wp_schedule_single_event( time() + $expires, 'fastpay_wc_check_transaction_status', array( $order_id ) );

        $this->log( $source, 'QR code generated for order #' . $order_id );

        // Set transient for FastPay payment page URL, so that we can call it again within given time
        set_transient( 'fastpay_wc_order_payment_url_' . $order_id, $response['code'], $response['expires_in'] );

        // Redirect to payment page
        return array(
            'result'   => 'success',
            'redirect' => $response['code'], // FastPay payment URL
        );

    }

    // Parameters to be passed when generating QR code
    private function get_qr_code_params( WC_Order $order ) {

        $state_code = $order->get_billing_state() ?: $order->get_shipping_state();
        $country_code = $order->get_billing_country() ?: $order->get_shipping_country();

        $wc_countries = WC()->countries;

        $state = isset( $wc_countries->get_states( $country_code )[ $state_code ] ) ? $wc_countries->get_states( $country_code )[ $state_code ] : $state_code;
        $country = isset( $wc_countries->countries[ $country_code ] ) ? $wc_countries->countries[ $country_code ] : $country_code;

        $shopper_details = array(
            'name'      => $order->get_formatted_billing_full_name() ?: $order->get_formatted_shipping_full_name(),
            'email'     => $order->get_billing_email(),
            'phone'     => $order->get_billing_phone(),
            'address_1' => $order->get_billing_address_1() ?: $order->get_shipping_address_1(),
            'address_2' => $order->get_billing_address_2() ?: $order->get_shipping_address_2(),
            'city'      => $order->get_billing_city() ?: $order->get_shipping_city(),
            'postcode'  => $order->get_billing_postcode() ?: $order->get_shipping_postcode(),
            'state'     => $state,
            'country'   => $country,
        );

        $redirect_url = $order->get_checkout_order_received_url();
        $callback_url = WC()->api_request_url( get_class( $this ) );

        $params = array(
            'omni_reference'     => wc_get_omni_reference_fpn_by_fave( $order->get_id() ),
            'total_amount_cents' => strval($order->get_total()* 100),
            'app_id'             => $this->app_id,
            'outlet_id'          => $this->outlet_id,
            'shopper_details'    => $shopper_details,
            'redirect_url'       => $redirect_url,
            'callback_url'       => $callback_url,
            'format'             => 'web_url',
        );

        return $params;

    }

    // Handle IPN callback
    public function handle_ipn_callback() {
        $source = get_class($this) . '|' . __FUNCTION__;
        if ( $response = $this->fastpay->get_ipn_response() ) {

            $this->log( $source, 'IPN (callback) response: ' . wp_json_encode( $response ) );

            $order_id = wc_get_order_id_by_omni_reference_fpn_by_fave( $response['omni_reference'] );
            $order = wc_get_order( $order_id );

            if ( !$order ) {
                $this->log( $source, 'Order #' . $order_id . ' not found' );
                return false;
            }

            // Check if the payment already marked as paid
            if ( get_post_meta( $order_id, $response['receipt_id'], true ) === 'paid' ) {
                return false;
            }

            try {
                $this->log( $source, 'Verifying signature (callback) for order #' . $order->get_id() );
                $this->fastpay->validate_ipn_response( $response );
            } catch ( Exception $e ) {
                $this->log( $source, $e->getMessage() );
                wp_die( $e->getMessage(), 'FastPay IPN', array( 'response' => 500 ) );
            } finally {
                $this->log( $source, 'Verified signature (callback) for order #' . $order->get_id() );
            }

            switch ( $response['status'] ) {
                case 'successful':
                    self::handle_success_payment( $order, $response );
                    break;

                case 'pending_payment':
                    self::handle_pending_payment( $order, $response );
                    break;

                default:
                    self::handle_failed_payment( $order, $response );
                    break;
            }

            exit;
        }

        $this->log( $source, 'IPN request (callback) failed' );

        wp_die( 'FastPay IPN request failed', 'FastPay IPN', array( 'response' => 500 ) );

    }

    // Handle success payment
    private static function handle_success_payment( WC_Order $order, $response ) {
        $source = 'FastPayNow_By_Fave_WC_Gateway' . '|' . __FUNCTION__;
        $logger =  wc_get_logger();

        update_post_meta( $order->get_id(), '_fastpay_omni_reference', $response['omni_reference'] );
        update_post_meta( $order->get_id(), '_transaction_id', $response['receipt_id'] );
        update_post_meta( $order->get_id(), $response['receipt_id'], 'paid' );

        $order->payment_complete();

        $reference = '<br>Receipt ID: ' . $response['receipt_id'];
        $reference .= '<br>Sandbox: ' . ( wc_get_setting_fpn_by_fave( 'sandbox' ) === 'yes' ? __( 'Yes', 'wc-fastpaynow-by-fave' ) : __( 'No', 'wc-fastpaynow-by-fave' ) );

        $order->add_order_note( esc_html__( 'Payment success!', 'wc-fastpaynow-by-fave' ) . $reference );

        $logger->add( 'wc-fastpaynow-by-fave', '[' . $source . "] " . 'Order #' . $order->get_id() . ' has been marked as Paid' );
    }

    // Handle pending payment
    private static function handle_pending_payment( WC_Order $order, $response, $add_note = true ) {
        $source = 'FastPayNow_By_Fave_WC_Gateway'  . '|' . __FUNCTION__;
        $logger =  wc_get_logger();

        if ( $add_note ) {
            $reference = '<br>Omni Reference: ' . $response['omni_reference'];
            $reference .= '<br>Sandbox: ' . ( wc_get_setting_fpn_by_fave( 'sandbox' ) === 'yes' ? __( 'Yes', 'wc-fastpaynow-by-fave' ) : __( 'No', 'wc-fastpaynow-by-fave' ) );

            $order->add_order_note( esc_html__( 'Payment pending!', 'wc-fastpaynow-by-fave' ) . $reference );
        }

        $logger->add( 'wc-fastpaynow-by-fave', '[' . $source . "] " . 'Payment for order #' . $order->get_id() . ' is pending' );

        // Check transaction status again after 15 minutes
        wp_schedule_single_event( time() + 900, 'fastpay_wc_check_transaction_status', array( $order->get_id() ) );

    }

    // Handle failed payment
    private static function handle_failed_payment( WC_Order $order, $response, $add_note = true ) {
        $source = 'FastPayNow_By_Fave_WC_Gateway' . '|' . __FUNCTION__;
        $logger =  wc_get_logger();

        if ( $add_note ) {
            $reference = '<br>Receipt ID: ' . $response['receipt_id'];
            $reference .= '<br>Sandbox: ' . ( wc_get_setting_fpn_by_fave( 'sandbox' ) === 'yes' ? __( 'Yes', 'wc-fastpaynow-by-fave' ) : __( 'No', 'wc-fastpaynow-by-fave' ) );

            $order->add_order_note( esc_html__( 'Payment failed!', 'wc-fastpaynow-by-fave' ) . $reference );
        }

        $logger->add( 'wc-fastpaynow-by-fave', '[' . $source . "] " . 'Payment for order #' . $order->get_id() . ' is failed' );
    }

    // Check transaction status for the order
    public static function check_transaction_status( $order_id ) {
        $source = 'FastPayNow_By_Fave_WC_Gateway' . '|' . __FUNCTION__;
        $logger =  wc_get_logger();
        $logger->add( 'wc-fastpaynow-by-fave', '[' . $source . "] " . 'Getting transaction status for order #' . $order_id );


        if ( !$order = wc_get_order( $order_id ) ) {
            $logger->add( 'wc-fastpaynow-by-fave', '[' . $source . "] " . 'Order #' . $order_id . ' not found' );
            return false;
        }

        if ( $order->has_status( 'cancelled' ) ) {
            $logger->add( 'wc-fastpaynow-by-fave', '[' . $source . "] " . 'Order #' . $order_id . ' status is cancelled' );
            return false;
        }

        $transaction_id     = get_post_meta( $order_id, '_transaction_id', true );
        $transaction_status = get_post_meta( $order_id, $transaction_id, true );

        // Check if the payment already marked as paid
        if ( $transaction_status === 'paid' ) {
            $logger->add( 'wc-fastpaynow-by-fave', '[' . $source . "] " . 'Order #' . $order_id . ' already marked as paid' );
            return false;
        }

        try {
            $params = array( 'app_id' => wc_get_app_id_fpn_by_fave() );

            // Check the payment status by receipt ID or Omni reference
            if ( $transaction_id ) {
                $params['receipt_id'] = $transaction_id;
            } else {
                $params['omni_reference'] = wc_get_omni_reference_fpn_by_fave( $order_id );
            }

            $fastpay = new FastPayNow_By_Fave_WC_API();
            list( $code, $response ) = $fastpay->get_transaction( $params );

            // This probably because the QR code expired.
            // Update suffix for Omni reference ID when the QR code expired,
            // so the reference ID will always be unique on new QR code generation.
            if ( !isset( $response['status'] ) ) {
                wc_update_omni_reference_suffix_fpn_by_fave( $order_id );
                return false;
            }

            switch ( $response['status'] ) {
                case 'successful':
                    self::handle_success_payment( $order, $response );
                    break;

                case 'pending_payment':
                    self::handle_pending_payment( $order, $response, false, false );
                    break;

                default:
                    self::handle_failed_payment( $order, $response, false );
                    break;
            }

        } catch ( Exception $e ) {}

    }

    public function on_thankyou_page_load( $order_id ) {
        $source = get_class($this) . '|' . __FUNCTION__;

        if ( !$this->validate_required_settings() ) {
            $this->log( $source, "The required settings is not set up." );
            return false;
        }

        if ( !$order = wc_get_order( $order_id ) ) {
            $this->log( $source, "Order " . $order_id . " not found" );
            return false;
        }

        if ( $order->get_payment_method() !== $this->id ) {
            $this->log( $source, "Payment for " . $order_id . " is not FastPayNow by Fave." );
            return false;
        }

        $transaction_id = get_post_meta( $order_id, '_transaction_id', true );

        // Check if the payment already marked as paid
        if ( get_post_meta( $order_id, $transaction_id, true ) === 'paid' ) {
            $this->log( $source, "Order " . $order_id . " already marked as paid" );
            return true;
        }

        if ( $order->has_status( 'cancelled' ) ) {
            $this->log( $source, "Order " . $order_id . " status is cancelled" );
            return false;
        }

        $transaction_id     = get_post_meta( $order_id, '_transaction_id', true );
        $transaction_status = get_post_meta( $order_id, $transaction_id, true );

        try {
            $this->log( $source, "Call GET transaction API for " . $order_id . "begin" );

            $params = array( 'app_id' => wc_get_app_id_fpn_by_fave() );

            $params['omni_reference'] = wc_get_omni_reference_fpn_by_fave( $order_id );

            $fastpay = new FastPayNow_By_Fave_WC_API();
            list( $code, $response ) = $fastpay->get_transaction( $params );

            // This probably because the QR code expired.
            // Update suffix for Omni reference ID when the QR code expired,
            // so the reference ID will always be unique on new QR code generation.
            if ( !isset( $response['status'] ) ) {
                wc_update_omni_reference_suffix_fpn_by_fave( $order_id );
                return false;
            }

            switch ( $response['status'] ) {
                case 'successful':
                    $this->log( $source, "Transaction status for " . $order_id . " is success" );
                    return true;
                    break;

                case 'pending_payment':
                    if ( $payment_url = get_transient( 'fastpay_wc_order_payment_url_' . $order_id ) ) {
                        $this->log( $source, "Transaction status for " . $order_id . " is pending_payment. Redirect user to " . $payment_url );
                        wp_redirect( $payment_url );
                        exit;
                    }else{
                        $this->log( $source, "Transaction status for " . $order_id . " is pending_payment. Show fail payment notice" );
                        wc_print_notice( 'Payment failed!', 'notice' );
                        wp_redirect( html_entity_decode( wc_get_cancel_order_url_fpn_by_fave( $order ) ) );
                        exit;
                    }
                    break;

                case 'payment_processing':
                    if ( $payment_url = get_transient( 'fastpay_wc_order_payment_url_' . $order_id ) ) {
                        $this->log( $source, "Transaction status for " . $order_id . " is payment_processing. Redirect user to " . $payment_url );
                        wp_redirect( $payment_url );
                        exit;
                    }else{
                        wc_print_notice( 'Payment is processing!', 'notice' );
                        return false;
                    }
                    break;

                default:
                    wc_print_notice( 'Payment failed!', 'notice' );
                    wp_redirect( html_entity_decode( wc_get_cancel_order_url_fpn_by_fave( $order ) ) );
                    break;
            }

        } catch ( Exception $e ) {}

        exit;
    }

    // Cancel order page
    // Refer WC_Form_Handle
    // We create custom cancel order page since FastPay will redirect to "process_payment" page whenever the payment failed
    // So, we need to have custom cancel order page that does not run cancel_order function as the order has been cancelled already.
    public static function cancel_order_page() {

        if (
            isset( $_GET['fastpay_cancel_order'] ) &&
            isset( $_GET['order'] ) &&
            isset( $_GET['order_id'] ) &&
            ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'fastpay-wc-cancel-order' ) )
        ) {
            wc_nocache_headers();

            $order_key       = wp_unslash( sanitize_text_field( $_GET['order'] ) );
            $order_id        = absint( $_GET['order_id'] );
            $order           = wc_get_order( $order_id );
            $user_can_cancel = current_user_can( 'cancel_order', $order_id );

            if (
                $user_can_cancel
                && $order->get_id() === $order_id
                && hash_equals( $order->get_order_key(), $order_key )
            ) {

                $notice = __( 'Payment failed. Please Please retry to complete the payment.', 'wc-fastpaynow-by-fave' );

                wc_print_notice( $notice, apply_filters( 'woocommerce_order_cancelled_notice_type', 'notice' ) );

                echo '<a class="button" href="' . esc_attr( $order->get_checkout_payment_url() ) . '">' . esc_html__( 'Retry payment', 'wc-fastpaynow-by-fave' ) . '</a>';
            }

        }

    }

    // Remove empty cart content in FastPay custom cancel order page
    public static function cancel_order_page_remove_wc_content() {

        if (
            isset( $_GET['fastpay_cancel_order'] ) &&
            isset( $_GET['order'] ) &&
            isset( $_GET['order_id'] ) &&
            ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'fastpay-wc-cancel-order' ) )
        ) {
            remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message' );
        }

    }

    // Remove WooCommerce "Return to Shop" button in FastPay custom cancel order page by changing the shop page ID to 0
    // Refer WooCommerce file - cart-empty.php
    public static function cancel_order_page_remove_wc_button( $page_id ) {

        if (
            isset( $_GET['fastpay_cancel_order'] ) &&
            isset( $_GET['order'] ) &&
            isset( $_GET['order_id'] ) &&
            ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'fastpay-wc-cancel-order' ) )
        ) {
            return 0;
        }

        return $page_id;

    }

    // Check if the order can be refunded via FastPay
    public function can_refund_order( $order ) {
        return $this->validate_required_settings() && $order && $order->get_transaction_id();
    }

    // Refund the order through FastPay (only for full refund)
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $source = get_class($this) . '|' . __FUNCTION__;
        $order = wc_get_order( $order_id );

        if ( $order->get_total() !== $amount ) {
            return new WP_Error( 'error', __( 'Refund failed! FastPay only process full refund.', 'wc-fastpaynow-by-fave' ) );
        }

        if ( !$this->can_refund_order( $order ) ) {
            return new WP_Error( 'error', __( 'Refund failed!', 'wc-fastpaynow-by-fave' ) );
        }

        $this->log( $source, 'Processing refund for order #' . $order_id );

        try {

            $params = array(
                'omni_reference' => wc_get_omni_reference_fpn_by_fave( $order_id ),
                'app_id'         => $this->app_id,
                'status'         => 'refunded',
            );

            list( $code, $response ) = $this->fastpay->update_transaction( $params );

            // Refund error with specific message
            if ( isset( $response['error_description'] ) ) {
                $order->add_order_note( esc_html__( 'Refund failed! ', 'wc-fastpaynow-by-fave' ) . $response['error_description'] . '. Customers will have to contact Fave to resolve the dispute.' );
                $this->log( $source, 'Refund for order ' . $order_id . ' failed: ' . $response['error_description'] );

                return new WP_Error( 'error', 'Refund failed! '. esc_html($response['error_description']) . '. Customers will have to contact Fave to resolve the dispute.' );
            }

            // General refund error
            if ( $code !== 201 ) {
                $order->add_order_note( esc_html__( 'Refund failed! ', 'wc-fastpaynow-by-fave' ) . 'Customers will have to contact Fave to resolve the dispute.' );
                $this->log( $source, 'Refund for order ' . $order_id . ' failed!' );
                return new WP_Error( 'error', __( 'Refund failed! ', 'wc-fastpaynow-by-fave' ). 'Customers will have to contact Fave to resolve the dispute.' );
            }

            $reference = '<br>Receipt ID: ' . $response['receipt_id'];
            $reference .= '<br>Sandbox: ' . ( $this->sandbox ? __( 'Yes', 'wc-fastpaynow-by-fave' ) : __( 'No', 'wc-fastpaynow-by-fave' ) );

            // Success refund
            $order->add_order_note( esc_html__( 'Refund success!', 'wc-fastpaynow-by-fave' ) . $reference );
            $this->log( $source, 'Refund processed for order #' . $order_id );

        } catch ( Exception $e ) {
            $this->log( $source, $e->getMessage() );
            return new WP_Error( 'error', $e->get_error_message() );
        }

        return true;

    }

    // Fastpay Checkout Link from Product
    public function generate_fastpay_link_from_product_page( $product_id, $quantity, $variation_id ) {
        global $woocommerce;
        $source = get_class($this) . '|' . __FUNCTION__;
        $user_ip = WC_Geolocation::get_ip_address();

        $gateways = WC()->payment_gateways->get_available_payment_gateways();

        $product = wc_get_product( $product_id );
        $order   = wc_create_order();


        if ( get_current_user_id() !== 0 ) {
            $userid = get_current_user_id();
        } else {
            wp_set_current_user( 0 );
            $userid = get_current_user_id();
            $address = array(
                'first_name' => 'Guest'
            );
            $order->set_address( $address, 'billing' );
        }

        update_post_meta($order->id, '_customer_user', $userid);

        $order->set_payment_method( $gateways[ 'fastpaynow_by_fave' ] );
        $order->set_created_via( 'checkout' );
        $order->set_status( 'pending' );

        if ( $product->is_type( 'variable' ) ) {
            $this->create_variation_order( $order, $product, $quantity, $variation_id );
        } else {
            $this->create_simple_order( $order, $product, $quantity );
        }

        if ( empty($_SERVER['HTTPS']) ) {
            $this->log( $source, 'Please enable SSL on your site in order to call our API' );
            exit("Please enable SSL on your site to call our API");
        }

        $params = $this->get_express_qr_code_params( $order );
        $this->log( $source, 'QR code Parameters' . json_encode($params) );
        $api_key = wc_get_setting_fpn_by_fave('api_key');
        $this->log( $source, 'API Key' . $api_key );

        $signature = FastPayNow_By_Fave_Utils::generate_json_sign($params, $api_key);
        $params["sign"] = $signature;

        $this->init_api();

        list( $code, $response ) = $this->fastpay->generate_fastpay_link( $params );

        $expires = isset( $response['expires_in'] ) ? $response['expires_in'] : 360;

        $url = $response['code'];

        // Check transaction status after QR code expires
        wp_schedule_single_event( time() + $expires, 'fastpay_wc_check_transaction_status', array( $order->get_id() ) );

        $this->log( $source, 'QR code generated for order #' . $order->get_id() );

        // Set transient for fastpay payment page URL, so that we can call it again within given time
        set_transient( 'fastpay_wc_order_payment_url_' . $user_ip, $url, $response['expires_in'] );

        wp_send_json($response, 200);
        exit();
    }

    // Fastpay Checkout Link from Checkout Page
    public function generate_fastpay_link_from_checkout_page(){
        // Mainly use this for checkout but the shipping and billing details is not saved to this order
        global $woocommerce;
        $source = get_class($this) . '|' . __FUNCTION__;
        $user_ip = WC_Geolocation::get_ip_address();

        $gateways = WC()->payment_gateways->get_available_payment_gateways();

        $cart = WC()->cart;
        $checkout = WC()->checkout();

        $order_id = $checkout->create_order(array());
        $order = wc_get_order($order_id);

        if ( get_current_user_id() !== 0 ) {
            $userid = get_current_user_id();
        } else {
            wp_set_current_user( 0 );
            $userid = get_current_user_id();
            $address = array(
                'first_name' => 'Guest'
            );
            $order->set_address( $address, 'billing' );
        }

        update_post_meta($order->id, '_customer_user', $userid);

        $order->set_payment_method( $gateways[ 'fastpaynow_by_fave' ] );
        $order->set_created_via( 'checkout' );
        $order->set_status( 'pending' );

        $order->calculate_totals();

        if ( empty($_SERVER['HTTPS']) ) {
            $this->log( $source, 'Please enable SSL on your site in order to call our API' );
            exit("Please enable SSL on your site to call our API");
        }

        $params = $this->get_express_qr_code_params( $order );
        $this->log( $source, 'QR code Parameters' . json_encode($params) );
        $api_key = wc_get_setting_fpn_by_fave('api_key');
        $this->log( $source, 'API Key' . $api_key );
        $signature = FastPayNow_By_Fave_Utils::generate_json_sign($params, $api_key);
        $params["sign"] = $signature;

        $this->init_api();

        list( $code, $response ) = $this->fastpay->generate_fastpay_link( $params );


        if(isset( $response['error'] )){
            wp_send_json_error("Fastpay API called failed. Please contact your admin for support.");
            exit();
        }

        $expires = isset( $response['expires_in'] ) ? $response['expires_in'] : 360;

        $url = $response['code'];

        // Check transaction status after QR code expires
        wp_schedule_single_event( time() + $expires, 'fastpay_wc_check_transaction_status', array( $order->get_id() ) );

        $this->log( $source, 'QR code generated for order #' . $order->get_id() );

        // Set transient for fastpay payment page URL, so that we can call it again within given time
        set_transient( 'fastpay_wc_order_payment_url_' . $user_ip, $url, $response['expires_in'] );

        //$cart->empty_cart();

        wp_send_json($response, 200);
        exit();
    }

    private function get_express_qr_code_params( $order ) {

        $line_items = array();

        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            $line_items[] = array(
                "name"         => $item->get_name(),
                "product_id"   => $item->get_product_id(),
                "variation_id" => $item->get_variation_id(),
                "image"        => wp_get_attachment_image_url( $product->get_image_id(), 'full' ),
                "currency"     => $order->get_currency(),
                "price"        => number_format((float)$product->get_price(), 2, '.', ''),
                "subtotal"     => number_format((float)$item->get_subtotal(), 2, '.', ''),
                "quantity"     => $item->get_quantity(),
                "total_tax"    => $item->get_total_tax(),
            );
        }

        $order_params = array (
            "id"             => $order->get_id(),
            "currency"       => $order->get_currency(),
            "discount_total" => $order->get_discount_total(),
            "shipping_total" => $order->get_shipping_total(),
            "total"          => $order->get_total(),
            "subtotal"       => number_format((float)$order->get_subtotal(), 2, '.', ''),
            "tax_total"      => $order->get_total_tax(),
            "line_items"     => $line_items,
        );

        $redirect_url = $order->get_checkout_order_received_url();
        $callback_url = WC()->api_request_url( get_class( new FastPayNow_By_Fave_WC_Gateway() ) );
        $sandbox = get_option('wc_fastpay_sandbox_tab') == 'yes';

        $params = array(
            'omni_reference'     => wc_get_omni_reference_fpn_by_fave( $order->get_id() ),
            'total_amount_cents' => absint( $order->get_total() * 100 ),
            'app_id'             => wc_get_setting_fpn_by_fave( 'app_id' ) ?: wc_get_app_id_fpn_by_fave(),
            'outlet_id'          => wc_get_setting_fpn_by_fave( 'outlet_id' ),
            'redirect_url'       => $redirect_url,
            'callback_url'       => $callback_url,
            'shipping_info_url'  => $this->get_current_host('/wp-json/wc/v3/get_shipping_options'),
            'update_order_url'   => $this->get_current_host('/wp-json/wc/v3/update_order'),
            'order'              => $order_params,
            'qr_format'          => 'web_url',
            'skip_email'         => get_option('wc_fastpay_skip_email_tab') == 'yes',
            'test'               => $sandbox,
        );

        return $params;

    }

    private function create_variation_order( $order, $product, $quantity, $variation_id ) {

        $product_variation_data = array();
        $products               = $product->get_available_variations();

        foreach ( $products as $product_variation ) {
            if( $product_variation['variation_id'] == $variation_id ) {
                $variation_id = $product_variation['variation_id'];
                $product_variation_data['variation'] = $product_variation['attributes'];
            }
        }

        if ( $variation_id ) {
            $product = new WC_Product_Variation( $variation_id );

            $order->add_product( $product, $quantity, $product_variation_data );
            $order->calculate_totals();
        }

    }

    private function create_simple_order( $order, $product, $quantity ) {

        $order->add_product( $product, $quantity );
        $order->calculate_totals();

    }

    private function get_current_host($path) {
        $protocol = null;
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        return sanitize_url($protocol . $_SERVER['HTTP_HOST'] . $path);
    }
}
