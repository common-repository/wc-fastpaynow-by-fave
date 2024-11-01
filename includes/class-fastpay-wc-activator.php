<?php
if ( !defined( 'ABSPATH' ) ) exit;

class FastPayNow_By_Fave_WC_Activator {

    // Fired during plugin activation
    public static function activate() {

        // Generate 3 random number as unique site ID to be used in Omni reference
        if ( !get_option( 'fastpay_wc_unique_site_id' ) ) {
            update_option( 'fastpay_wc_unique_site_id', wp_rand( 100, 999 ) );
        }

    }

}
