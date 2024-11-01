<?php
if ( !defined( 'ABSPATH' ) ) exit;

class FastPayNow_By_Fave_WC_Public {

    // Register hooks
    public function __construct() {

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'promo_messaging_product' ) );
        add_action( 'woocommerce_after_shop_loop_item', array( $this, 'promo_messaging_product_loop' ) );
        add_filter( 'woocommerce_blocks_product_grid_item_html', array( $this, 'promo_messaging_product_grid' ), 10, 3 );
        add_filter( 'woocommerce_cart_totals_order_total_html', array( $this, 'promo_messaging_cart' ) );

    }

    // Enqueue styles and scripts
    public function enqueue_scripts() {

        $promo_js_url = 'https://partners.myfave.gdn/js/fpo_promo.js';

        if ( FASTPAYNOW_BY_FAVE_WC_STAGING === "yes" ) {
            $promo_js_url = 'https://partners.myfave.gdn/js/fpo_promo_staging.js';
        }

        wp_enqueue_style( 'wc-fastpaynow-by-fave', FASTPAYNOW_BY_FAVE_WC_URL . 'assets/css/style.min.css', array(), FASTPAYNOW_BY_FAVE_WC_VERSION );
        wp_enqueue_script( 'fastpay-promo', $promo_js_url, array( 'jquery' ), current_time( 'Ymd' ), true );

    }

    // Promotional messaging at product level pages
    public function promo_messaging_product( $product ) {

        global $product;

        $promotional_messaging = wc_get_setting_fpn_by_fave( 'promotional_messaging_product' );

        if ( $promotional_messaging === 'yes' ) {
            echo $this->get_promo_messaging_product( $product );
        }

    }

    // Promotional messaging at product loop level pages
    public function promo_messaging_product_loop( $product ) {

        global $product;

        $promotional_messaging = wc_get_setting_fpn_by_fave( 'promotional_messaging_product_grid' );

        if ( $promotional_messaging === 'yes' ) {
            echo $this->get_promo_messaging_product( $product );
        }

    }

    // Promotional messaging at product grid level pages
    public function promo_messaging_product_grid( $html, $data, $product ) {

        $promotional_messaging = wc_get_setting_fpn_by_fave( 'promotional_messaging_product_grid' );

        if ( $promotional_messaging !== 'yes' ) {
            return $html;
        }

        // Add promotional messaging before Add to Cart button
        return str_replace( "{$data->button}", $this->get_promo_messaging_product( $product ) . "{$data->button}", $html );

    }

    // Get promotional messaging for product and product grid level pages
    private function get_promo_messaging_product( $product ) {

        if ( $product->is_type('variation') ) {
            $min_price = $product->get_variation_price( 'min' );
            $max_price = $product->get_variation_price( 'max' );

            if ( $max_price > $min_price ) {
                $price = $min_price . ' - ' . $max_price;
            } else {
                $price = $max_price;
            }
        } else {
            $price = $product->get_price();
        }

        return wc_get_promo_html_fpn_by_fave( $price );

    }

    // Promotional messaging at cart level pages
    public function promo_messaging_cart( $total ) {

        if ( !is_cart() ) {
            return $total;
        }

        $promotional_messaging_cart = wc_get_setting_fpn_by_fave( 'promotional_messaging_cart' );

        if ( $promotional_messaging_cart === 'yes' ) {
            $total .= wc_get_promo_html_fpn_by_fave( WC()->cart->total );
        }

        return $total;

    }

}
new FastPayNow_By_Fave_WC_Public();
