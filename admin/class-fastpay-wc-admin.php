<?php
if ( !defined( 'ABSPATH' ) ) exit;

class FastPayNow_By_Fave_WC_Admin {

    // Register hooks
    public function __construct() {

        add_action( 'plugin_action_links_' . FASTPAYNOW_BY_FAVE_WC_BASENAME, array( $this, 'register_settings_link' ) );
        add_action( 'admin_notices', array( $this, 'woocommerce_notice' ) );
        add_action( 'admin_notices', array( $this, 'currency_not_supported_notice' ) );

    }

    // Register plugin settings link
    public function register_settings_link( $links ) {

        $url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=fastpaynow_by_fave' );
        $label = esc_html__( 'Settings', 'wc-fastpaynow-by-fave' );

        $settings_link = sprintf( '<a href="%s">%s</a>', $url, $label );
        array_unshift( $links, $settings_link );

        return $links;

    }

    // Check if WooCommerce is installed and activated
    private function is_woocommerce_activated() {
        return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    }

    // Show notice if WooCommerce not installed
    public function woocommerce_notice() {

        if ( !$this->is_woocommerce_activated() ) {
            show_wc_notice_fpn_by_fave( esc_html__( 'WooCommerce needs to be installed and activated.', 'wc-fastpaynow-by-fave' ), 'error' );
        }

    }

    // Show notice if currency selected is not supported by FastPay
    public function currency_not_supported_notice() {

        if ( !wc_get_country_fpn_by_fave() ) {
            show_wc_notice_fpn_by_fave( sprintf( __( 'Currency not supported by FastPay. <a href="%s">Change currency</a>', 'wc-fastpaynow-by-fave' ), admin_url( 'admin.php?page=wc-settings&tab=general#woocommerce_currency' ) ), 'error' );
        }

    }

}
new FastPayNow_By_Fave_WC_Admin();
