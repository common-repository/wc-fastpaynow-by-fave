<?php
if ( !defined( 'ABSPATH' ) ) exit;

class FastPayNow_By_Fave_WC_API extends FastPayNow_By_Fave_WC_Client {

    // Initialize API
    public function __construct( $api_key = null, $country = null, $sandbox = false ) {

        $this->api_key = wc_get_setting_fpn_by_fave( 'api_key', $api_key );
        $this->country = $country ?: wc_get_country_fpn_by_fave();
        $this->sandbox = wc_get_setting_fpn_by_fave( 'sandbox', $sandbox ) === 'yes' ? true : false;
        $this->staging_url = wc_get_setting_fpn_by_fave( 'staging_url', '' );
        $this->debug   = wc_get_setting_fpn_by_fave( 'debug' ) === 'yes' ? true : false;

    }

    // Request a QR code
    public function generate_qr_code( array $params ) {
        return $this->post( 'api/fpo/v1/' . $this->country . '/qr_codes', $params, $is_fastpay = false );
    }

    // Request Fastpay QR code
    public function generate_fastpay_link( array $params ) {
        return $this->post( 'api/fastpay/v1/' . $this->country . '/checkout', $params, $is_fastpay = true );
    }

    // Get a transaction
    public function get_transaction( array $params ) {
        return $this->get( 'api/fpo/v1/' . $this->country . '/transactions', $params, $is_fastpay = false );
    }

    // Update a transaction
    public function update_transaction( array $params ) {
        return $this->post( 'api/fpo/v1/' . $this->country . '/transactions', $params, $is_fastpay = false );
    }

}
