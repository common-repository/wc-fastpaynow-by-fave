<?php

/**
 * @package    FastpayNow_By_Fave_For_Woocommerce_Button_Manager
 * @subpackage FastpayNow_By_Fave_For_Woocommerce_Button_Manager/public
 * @author     Ben <ben.lim@myfave.com>
 */
class FastpayNow_By_Fave_For_Woocommerce_Button_Manager {
    protected static $_instance = null;
    public $woocommerce_button_manager_settings;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->get_properties();
        if (!has_action('woocommerce_api_' . strtolower('FastpayNow_By_Fave_For_Woocommerce_Button_Manager'))) {
            add_action('woocommerce_api_' . strtolower('FastpayNow_By_Fave_For_Woocommerce_Button_Manager'), array($this, 'handle_wc_api'));
        }
        include_once FASTPAYNOW_BY_FAVE_WC_PATH . '/includes/class-fastpay-wc-gateway.php';
        $this->request = new FastPayNow_By_Fave_WC_Gateway();
        $this->fastpay_add_hooks();
    }

    public function get_properties() {
        global $post;
        $this->product_id = null;
        if(!empty($post)){
            $this->product_id = $post->ID;
        }

        global $order;
        $this->order_id = null;
        if(!empty($order)){
            $this->order_id = json_encode($order);
        }

        $this->show_fastpay_button_on_checkout_page = wc_get_setting_fpn_by_fave( 'enabled', '' );
        $this->show_fastpay_button_on_product_page = wc_get_setting_fpn_by_fave( 'product_page_button', '' );
        // $this->show_fastpay_button_on_cart_page = wc_get_setting_fpn_by_fave( 'cart_page_button', '' );

        $this->checkout_button_color = wc_get_setting_fpn_by_fave( 'checkout_button_color', '' );
        if( $this->checkout_button_color === "Violet blue" ) {
            $this->checkout_button_color_code = "#4B23EA";
            $this->checkout_button_font_color_code = "#FFFFFF";
            $this->checkout_button_border_color_code = "#4B23EA";
            $this->checkout_lightning_svg_location = FASTPAYNOW_BY_FAVE_WC_URL . "/assets/images/WhiteLightningIcon.svg";
        } elseif( $this->checkout_button_color === "Black" ) {
            $this->checkout_button_color_code = "#000000";
            $this->checkout_button_font_color_code = "#FFFFFF";
            $this->checkout_button_border_color_code = "#000000";
            $this->checkout_lightning_svg_location = FASTPAYNOW_BY_FAVE_WC_URL . "/assets/images/WhiteLightningIcon.svg";
        } elseif( $this->checkout_button_color === "White" ){
            $this->checkout_button_color_code = "#FFFFFF";
            $this->checkout_button_font_color_code = "#4B23EA";
            $this->checkout_button_border_color_code = "#4B23EA";
            $this->checkout_lightning_svg_location = FASTPAYNOW_BY_FAVE_WC_URL . "/assets/images/PurpleLightningIcon.svg";
        }

        $this->product_page_button_color = wc_get_setting_fpn_by_fave( 'product_page_button_color', '' );
        if( $this->product_page_button_color === "Violet blue" ) {
            $this->product_page_button_color_code = "#4B23EA";
            $this->product_page_button_font_color_code = "#FFFFFF";
            $this->product_page_button_border_color_code = "#4B23EA";
            $this->product_page_lightning_svg_location = FASTPAYNOW_BY_FAVE_WC_URL . "/assets/images/WhiteLightningIcon.svg";
        } elseif( $this->product_page_button_color === "Black" ) {
            $this->product_page_button_color_code = "#000000";
            $this->product_page_button_font_color_code = "#FFFFFF";
            $this->product_page_button_border_color_code = "#000000";
            $this->product_page_lightning_svg_location = FASTPAYNOW_BY_FAVE_WC_URL . "/assets/images/WhiteLightningIcon.svg";
        } elseif( $this->product_page_button_color === "White" ){
            $this->product_page_button_color_code = "#FFFFFF";
            $this->product_page_button_font_color_code = "#4B23EA";
            $this->product_page_button_border_color_code = "#4B23EA";
            $this->product_page_lightning_svg_location = FASTPAYNOW_BY_FAVE_WC_URL . "/assets/images/PurpleLightningIcon.svg";
        }

        // $this->cart_page_button_color = wc_get_setting_fpn_by_fave( 'cart_page_button_color', '' );
        // if( $this->cart_page_button_color === "Violet blue" ) {
        //     $this->cart_page_button_color_code = "#4B23EA";
        //     $this->cart_page_button_font_color_code = "#FFFFFF";
        //     $this->cart_page_button_border_color_code = "#4B23EA";
        //     $this->cart_page_lightning_svg_location = FASTPAYNOW_BY_FAVE_WC_URL . "/assets/images/WhiteLightningIcon.svg";
        // } elseif( $this->cart_page_button_color === "Black" ) {
        //     $this->cart_page_button_color_code = "#000000";
        //     $this->cart_page_button_font_color_code = "#FFFFFF";
        //     $this->cart_page_button_border_color_code = "#000000";
        //     $this->cart_page_lightning_svg_location = FASTPAYNOW_BY_FAVE_WC_URL . "/assets/images/WhiteLightningIcon.svg";
        // } elseif( $this->cart_page_button_color === "White" ){
        //     $this->cart_page_button_color_code = "#FFFFFF";
        //     $this->cart_page_button_font_color_code = "#4B23EA";
        //     $this->cart_page_button_border_color_code = "#4B23EA";
        //     $this->cart_page_lightning_svg_location = FASTPAYNOW_BY_FAVE_WC_URL . "/assets/images/PurpleLightningIcon.svg";
        // }

        $this->checkout_button_shape = wc_get_setting_fpn_by_fave( 'checkout_button_shape', '' );
        if( $this->checkout_button_shape === "Pill" ) {
            $this->checkout_button_border_radius = "5";
        } elseif( $this->checkout_button_shape === "Rectangle" ) {
            $this->checkout_button_border_radius = "0";
        }

        $this->product_page_button_shape = wc_get_setting_fpn_by_fave( 'product_page_button_shape', '' );
        if( $this->product_page_button_shape === "Pill" ) {
            $this->product_page_button_border_radius = "5";
        } elseif( $this->product_page_button_shape === "Rectangle" ) {
            $this->product_page_button_border_radius = "0";
        }

        // $this->cart_page_button_shape = wc_get_setting_fpn_by_fave( 'cart_page_button_shape', '' );
        // if( $this->cart_page_button_shape === "Pill" ) {
        //     $this->cart_page_button_border_radius = "5";
        // } elseif( $this->cart_page_button_shape === "Rectangle" ) {
        //     $this->cart_page_button_border_radius = "0";
        // }

        $this->checkout_button_label = wc_get_setting_fpn_by_fave( 'checkout_button_label' , '' );
        $this->product_page_button_label = wc_get_setting_fpn_by_fave( 'product_page_button_label' , '' );
        // $this->cart_page_button_label = wc_get_setting_fpn_by_fave( 'cart_page_button_label' , '' );
    }

    public function fastpay_add_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));

        add_action('woocommerce_after_add_to_cart_form', array($this, 'display_fastpay_button_product_page'), 1);

        if ($this->show_fastpay_button_on_checkout_page) {
            add_action('display_fastpay_button_checkout_page', array($this, 'display_fastpay_button_checkout_page'));
        }

        // if ($this->show_fastpay_button_on_cart_page) {
        //     add_action('woocommerce_proceed_to_checkout', array($this, 'display_fastpay_button_cart_page'), 11);
        // }

        // add_action('wp_loaded', array($this, 'ppcp_session_manager'), 999);
        // add_filter('the_title', array($this, 'ppcp_endpoint_page_titles'));
        // add_action('woocommerce_cart_emptied', array($this, 'maybe_clear_session_data'));
        //add_action('woocommerce_checkout_init', array($this, 'ppcp_checkout_init'));
        // add_action('woocommerce_available_payment_gateways', array($this, 'maybe_disable_other_gateways'));
        // add_filter('woocommerce_default_address_fields', array($this, 'filter_default_address_fields'));
        // add_action('woocommerce_checkout_process', array($this, 'copy_checkout_details_to_post'));
        // add_action('woocommerce_cart_shipping_packages', array($this, 'maybe_add_shipping_information'));
        // add_filter('body_class', array($this, 'ppcp_add_class_order_review_page'));
        // add_filter('woocommerce_coupons_enabled', array($this, 'ppcp_woocommerce_coupons_enabled'), 999, 1);
        // add_action('woocommerce_before_checkout_form', array($this, 'ppcp_order_review_page_description'), 9);
        // add_action('woocommerce_order_status_processing', array($this, 'ppcp_capture_payment'));
        // add_action('woocommerce_order_status_completed', array($this, 'ppcp_capture_payment'));
        // add_action('woocommerce_order_status_cancelled', array($this, 'ppcp_cancel_authorization'));
        // add_action('woocommerce_order_status_refunded', array($this, 'ppcp_cancel_authorization'));
        // add_filter('woocommerce_order_actions', array($this, 'ppcp_add_capture_charge_order_action'));
        // add_action('woocommerce_order_action_ppcp_capture_charge', array($this, 'ppcp_maybe_capture_charge'));
        // add_action('woocommerce_before_checkout_form', array($this, 'ppcp_update_checkout_field_details'));
        // add_action('woocommerce_review_order_before_submit', array($this, 'ppcp_cancel_button'));
        // add_action('wp_loaded', array($this, 'ppcp_create_webhooks'));
        // add_action('woocommerce_pay_order_after_submit', array($this, 'ppcp_add_order_id'));
        // add_action('wp_loaded', array($this, 'ppcp_prevent_add_to_cart_woo_action'), 1);
        // add_action('woocommerce_get_checkout_url', array($this, 'ppcp_woocommerce_get_checkout_url'), 9999, 1);
        // if ($this->show_on_checkout_page === true && $this->enable_checkout_button_top === true) {
        //     add_action('woocommerce_before_checkout_form', array($this, 'display_paypal_button_top_checkout_page'), 5);
        // }
    }

    public function enqueue_scripts() {
        $this->get_properties();
        $page = '';
        if (is_product()) {
            $page = 'product';
        } elseif (is_cart() && !WC()->cart->is_empty()) {
            $page = 'cart';
        } elseif (is_checkout_pay_page()) {
            $page = 'checkout';
        } elseif (is_checkout()) {
            $page = 'checkout';
        }

        wp_register_script('fastpay-checkout-for-woocommerce', FASTPAYNOW_BY_FAVE_WC_URL . '/public/js/fastpay-checkout-for-woocommerce.js', array('jquery'), time(), false);
        wp_localize_script('fastpay-checkout-for-woocommerce', 'fastpay_button_manager', array(
            'product_id' => $this->product_id,
            'order_id' => $this->order_id,
            'show_fastpay_button_on_checkout_page' => $this->show_fastpay_button_on_checkout_page,
            'show_fastpay_button_on_product_page' => $this->show_fastpay_button_on_product_page,
            // 'show_fastpay_button_on_cart_page' => $this->show_fastpay_button_on_cart_page,
            'checkout_button_color_code' => $this->checkout_button_color_code,
            'checkout_button_font_color_code' => $this->checkout_button_font_color_code,
            'checkout_button_border_color_code' => $this->checkout_button_border_color_code,
            'checkout_lightning_svg_location' => $this->checkout_lightning_svg_location,
            'checkout_button_border_radius' => $this->checkout_button_border_radius,
            'checkout_button_label' => $this->checkout_button_label,
            'product_page_button_color_code' => $this->product_page_button_color_code,
            'product_page_button_font_color_code' => $this->product_page_button_font_color_code,
            'product_page_button_border_color_code' => $this->product_page_button_border_color_code,
            'product_page_lightning_svg_location' => $this->product_page_lightning_svg_location,
            'product_page_button_border_radius' => $this->product_page_button_border_radius,
            'product_page_button_label' => $this->product_page_button_label,
            // 'cart_page_button_color_code' => $this->cart_page_button_color_code,
            // 'cart_page_button_font_color_code' => $this->cart_page_button_font_color_code,
            // 'cart_page_button_border_color_code' => $this->cart_page_button_border_color_code,
            // 'cart_page_lightning_svg_location' => $this->cart_page_lightning_svg_location,
            // 'cart_page_button_border_radius' => $this->cart_page_button_border_radius,
            // 'cart_page_button_label' => $this->cart_page_button_label,
            'page' => $page,
            'fastpay_link_from_product_page' => add_query_arg(array('fastpay_action' => 'generate_fastpay_link', 'source' => 'product'), WC()->api_request_url('FastpayNow_By_Fave_For_Woocommerce_Button_Manager')),
            'fastpay_link_from_checkout_page' => add_query_arg(array('fastpay_action' => 'generate_fastpay_link', 'source' => 'checkout'), WC()->api_request_url('FastpayNow_By_Fave_For_Woocommerce_Button_Manager'))
        ));
    }

    public function enqueue_styles() {
        wp_register_style('fastpay-checkout-for-woocommerce', FASTPAYNOW_BY_FAVE_WC_URL . 'public/css/fastpay-checkout-for-woocommerce.css', array(), time(), 'all');
    }

    public function display_fastpay_button_product_page() {
        global $product;
        if (!is_product() || !$product->is_in_stock() || $product->is_type('external') || $product->is_type('grouped')) {
            return;
        }
        wp_enqueue_script('fastpay-checkout-for-woocommerce');
        wp_enqueue_style("fastpay-checkout-for-woocommerce");
        echo '<div class="fastpay-button-container"><div id="fastpay_product"></div></div>';
    }

    public function display_fastpay_button_checkout_page() {
        wp_enqueue_script('fastpay-checkout-for-woocommerce');
        wp_enqueue_style("fastpay-checkout-for-woocommerce");
    }

    public function display_fastpay_button_cart_page() {
        wp_enqueue_script('fastpay-checkout-for-woocommerce');
        wp_enqueue_style("fastpay-checkout-for-woocommerce");
        echo '<div class="fastpay-button-container"><div id="fastpay_cart"></div><div class="fastpay-proceed-to-checkout-button-separator"></div></div>';
    }

    public function handle_wc_api() {
        if (!empty($_GET['fastpay_action'])) {
            switch ($_GET['fastpay_action']) {
                case "generate_fastpay_link":
                    if(isset($_GET['source']) && $_GET['source'] === 'product'){
                        $product_id = sanitize_text_field($_POST['id']);
                        $quantity = sanitize_text_field($_POST['quantity']);
                        $variation_id = sanitize_text_field($_POST['variation_id']);
                        $this->request->generate_fastpay_link_from_product_page($product_id, $quantity, $variation_id);
                    }
                    exit();
                    break;
            }
        }
    }
}
