<?php
if ( !defined( 'ABSPATH' ) ) exit;

// Get plugin setting by key
function wc_get_setting_fpn_by_fave( $key, $default = null ) {
    $settings = get_option( 'woocommerce_fastpaynow_by_fave_settings' );
    return !empty( $settings[ $key ] ) ? $settings[ $key ] : $default;
}

// Display notice
function show_wc_notice_fpn_by_fave( $message, $type = 'success' ) {
    printf( '<div class="notice notice-%1$s"><p><strong>%2$s:</strong> %3$s</p></div>', $type, esc_html__( 'FastPay for WooCommerce', 'wc-fastpaynow-by-fave' ), $message );
}

// Get supported currencies (based on supported countries)
function wc_get_supported_currencies_fpn_by_fave() {
    return array(
        'my' => 'MYR',
        'sg' => 'SGD',
        'id' => 'IDR',
    );
}

// Get unique site ID to be used in Omni reference
function wc_get_unique_site_id_fpn_by_fave() {

    $option_key = 'fastpay_wc_unique_site_id';

    // Generate 3 random number as unique site ID to be used in Omni reference
    if ( !get_option( $option_key ) ) {
        update_option( $option_key, wp_rand( 100, 999 ) );
    }

    return absint( get_option( $option_key ) );

}

// Get country based on WooCommerce currency set
function wc_get_country_fpn_by_fave() {

    if ( function_exists( 'get_woocommerce_currency' ) ) {
        return array_search( get_woocommerce_currency(), wc_get_supported_currencies_fpn_by_fave() );
    }
    return false;

}

// Get app ID (based on country/currency)
function wc_get_app_id_fpn_by_fave() {

    if ( FASTPAYNOW_BY_FAVE_WC_STAGING == "yes" ){
        return wc_get_setting_fpn_by_fave( 'app_id' );
    }else{
        $currency = get_woocommerce_currency();
        $sandbox = wc_get_setting_fpn_by_fave( 'sandbox' ) === 'yes';

        switch ( $currency ) {
            case 'MYR':
                return $sandbox ? 'f0l6wvuzfy' : 'uxap7442j9';
                break;

            case 'SGD':
                return $sandbox ? 'qzev1rcl9e' : 'gaov8uue5k';
                break;

            case 'IDR':
                return $sandbox ? '49mn9ulo0w' : 'tycvzt85ui';
                break;
        }

        return false;
    }
}

// Get gateway description by country
function wc_get_description_fpn_by_fave() {

    $country = wc_get_country_fpn_by_fave();

    // Description based on country
    switch ( $country ) {
        case 'my':
            return __( "Securely pay with your FastPay account or as a Guest.", 'wc-fastpaynow-by-fave' );
            break;

        case 'sg':
            return __( "Split payment in 3 with 0% interest. $3 OFF new user code: PINKONLINE\n\nT&C apply.", 'wc-fastpaynow-by-fave' );
            break;

        default:
            return __( 'Earn up to 15% cashback and stack up your rewards by using Fave, it’s that simple!', 'wc-fastpaynow-by-fave' );
            break;
    }

}

// Get the prefix to be used in Omni reference, based on the country
function wc_get_omni_reference_prefix_fpn_by_fave() {
    $prefix = wc_get_setting_fpn_by_fave( 'prefix' );

    if(!empty($prefix))
        return $prefix;
    else{
        $sandbox = wc_get_setting_fpn_by_fave( 'sandbox' ) === 'yes';

        $prefix = 'WC';
        $country = wc_get_country_fpn_by_fave();

        if ( !$country || $sandbox ) {
            return $prefix . 'M'; // eg: WCM
        }

        return $prefix . strtoupper( $country ); // eg: WCMY
    }
}

/**
 * Generate Omni reference by outlet and order ID.
 * ----------------------------------------------
 * Example of Omni reference:
 * WCMY-<outlet id><order_id>
 * WCMY-<outlet id><order_id>A
 * WCMY-<outlet id><order_id>AA
 * ----------------------------------------------
 */
function wc_get_omni_reference_fpn_by_fave( $order_id ) {

    // Get stored Omni reference (if have) - for successful payment
    if ( $omni_reference = get_post_meta( $order_id, '_fastpay_omni_reference', true ) ) {
        return $omni_reference;
    }

    $prefix    = wc_get_omni_reference_prefix_fpn_by_fave();
    $outlet_id = wc_get_setting_fpn_by_fave( 'outlet_id' );
    $suffix    = wc_get_omni_reference_suffix_fpn_by_fave( $order_id );
    $site_id   = wc_get_unique_site_id_fpn_by_fave();

    return $prefix . '-' . $outlet_id . $order_id . $suffix . $site_id;

}


/**
 * Get Omni reference suffix.
 * Everytime new QR code generated for the same order,
 * it will add 'A' letter on Omni reference suffix.
 * Refer Omni reference example.
 */
function wc_get_omni_reference_suffix_fpn_by_fave( $order_id ) {

    $suffix = null;
    $suffix_no = absint( get_post_meta( $order_id, '_fastpay_wc_omni_reference_suffix', true ) );

    if ( $suffix_no > 0 ) {
        $suffix = str_repeat( 'A', $suffix_no );
    }

    return $suffix;

}

// Update Omni reference suffix to generate unique reference ID
function wc_update_omni_reference_suffix_fpn_by_fave( $order_id ) {

    $meta_key = '_fastpay_wc_omni_reference_suffix';
    $current_suffix = absint( get_post_meta( $order_id, $meta_key, true ) );

    update_post_meta( $order_id, $meta_key, $current_suffix + 1 );

    // Delete existing payment URL, if have
    delete_transient( 'fastpay_wc_order_payment_url_' . $order_id );

    // Get latest Omni reference after suffix increment
    return absint( get_post_meta( $order_id, $meta_key, true ) );

}

// Get order ID by Omni reference (get running number only by removing the prefix and outlet ID)
function wc_get_order_id_by_omni_reference_fpn_by_fave( $omni_reference ) {

    $prefix    = wc_get_omni_reference_prefix_fpn_by_fave();
    $outlet_id = wc_get_setting_fpn_by_fave( 'outlet_id' );
    $site_id   = wc_get_unique_site_id_fpn_by_fave();

    /**
     * 1. Remove prefix and outlet ID
     * 2. Remove unqiue site ID
     * 3. Get only number, so we can get rid of Omni reference suffix
     */
    return absint( str_replace( array( $prefix . '-' . $outlet_id, $site_id ), array( '', '' ), $omni_reference ) );

}

// HTML of FastPay promo widget
function wc_get_promo_html_fpn_by_fave( $price, $link = true ) {

    $cashback_rate = wc_get_setting_fpn_by_fave( 'cashback_rate', 0 );
    $country       = wc_get_country_fpn_by_fave();
    $outlet_id     = wc_get_setting_fpn_by_fave( 'outlet_id' );

    $logo = '<img class="fastpay-logo" src="' . FASTPAYNOW_BY_FAVE_WC_URL . 'assets/images/PurpleLightningIcon.svg"/>';

    if ( $cashback_rate > 0 && $cashback_rate <= 99 ) {
        $content = sprintf( __( 'Get %1$s%% Cashback and split your payment with %2$s.', 'wc-fastpaynow-by-fave' ), $cashback_rate, $logo );
    } else {
        $content = sprintf( __( 'Split your payment with %s.', 'wc-fastpaynow-by-fave' ), $logo );
    }

    // Include FastPay link
    if ( $link ) {
        $content .= sprintf( __( ' Learn more about <a href="%s" target="_blank" title="Fave - Double your savings, triple your rewards.">Fave</a>.', 'wc-fastpaynow-by-fave' ), 'https://discover.myfave.com/' );
    }

    return wp_kses_post(sprintf( '<div class="fastpay-widget" data-price="%1$s" data-cashbackrate="%2$s" data-country="%3$s" data-site="woocommerce" data-outlet="%4$s">%5$s</div>', $price, $cashback_rate, $country, $outlet_id, $content ));

}

// Get cancel order page URL
function wc_get_cancel_order_url_fpn_by_fave( $order ) {

    return wp_nonce_url(
        add_query_arg(
            array(
                'fastpay_cancel_order' => 'true',
                'order'                => $order->get_order_key(),
                'order_id'             => $order->get_id(),
            ),
            $order->get_cancel_endpoint()
        ),
        'fastpay-wc-cancel-order'
    );

}

add_filter( 'woocommerce_gateway_description', 'gateway_description_fpn_by_fave', 10, 2 );

function gateway_description_fpn_by_fave( $description_text, $payment_id ){
    $description = '';
    if ( strpos($payment_id, 'fastpay') !== false ) {
        $description .= $description_text . '<div class="fastpay-button-container"><div id="fastpay_checkout"></div></div>';
    }
    return wp_kses_post($description);
}

// Add query parameters for thankyou page
// add_filter( 'woocommerce_get_return_url', 'fpn_by_fave_customize_return_url', 10, 2 );
// add_filter( 'woocommerce_get_checkout_order_received_url', 'fpn_by_fave_customize_return_url', 10, 2 );

// function fpn_by_fave_customize_return_url( $return_url, $order ){
//     $query_args = array(
//         'receipt_id' => '',
//         'omni_reference' => wc_get_omni_reference_fpn_by_fave( $order->get_id() ),
//         'status' => '',
//         'total_amount_cents' => '',
//         'sign' => ''
//     );
//     return add_query_arg( $query_args, $return_url );
// }