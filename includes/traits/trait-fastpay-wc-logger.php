<?php
if ( !defined( 'ABSPATH' ) ) exit;

trait FastPayNow_By_Fave_WC_Logger {
    private $logger;

    protected function log( $source, $event ) {
        if ( !$this->logger ) {
            $this->logger = wc_get_logger();
        }
        if ( $this->logger && $this->debug ) {
            $this->logger->add( 'wc-fastpaynow-by-fave', '[' . $source . "] " . $event );
        }
    }
}
