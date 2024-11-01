<?php
if ( !defined( 'ABSPATH' ) ) exit;

abstract class FastPayNow_By_Fave_WC_Client {

    use FastPayNow_By_Fave_WC_Logger;

    const PRODUCTION_URL = 'https://omni.myfave.com/';
    const SANDBOX_URL    = 'https://omni.app.fave.ninja/';

    protected $api_key;
    protected $country;
    protected $staging_url;
    protected $sandbox = true;
    protected $debug = false;

    private function get_url( $route = null ) {
        if ( $this->sandbox ) {
            if ( FASTPAYNOW_BY_FAVE_WC_STAGING == "yes" ){
                return $this->staging_url . $route;
            }else{
                return self::SANDBOX_URL . $route;
            }
        } else {
            return self::PRODUCTION_URL . $route;
        }
    }

    private function get_headers() {
        return array(
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'X-WC-Version'  => FASTPAYNOW_BY_FAVE_WC_VERSION,
        );
    }

    protected function get( $route, $params = array(), $is_fastpay ) {
        return $this->request( $route, $params, $is_fastpay, 'GET' );
    }

    protected function post( $route, $params = array(), $is_fastpay ) {
        return $this->request( $route, $params, $is_fastpay );
    }

    protected function request( $route, $params = array(), $is_fastpay, $method = 'POST' ) {
        $source = get_class($this) . '|' . __FUNCTION__;

        if ( !( $this->api_key && $this->country ) ) {
            throw new Exception( 'Empty required parameter.' );
        }

        $url = $this->get_url( $route );
        $args['headers'] = $this->get_headers();

        $this->log( $source, 'URL: ' . $url );
        $this->log( $source, 'Headers: ' . wp_json_encode( $args['headers'] ) );

        // Generate API signature
        $params = (array)$params;
        $params['sign'] = $this->generate_api_signature( $params, $is_fastpay );

        if ( $params ) {
            $args['body'] = $method !== 'POST' ? $params : wp_json_encode( $params );
            $this->log( $source, 'Body: ' . wp_json_encode( $params ) );
        }

        // Set request timeout to 30 seconds
        $args['timeout'] = 30;

        switch ( $method ) {
            case 'GET':
                $response = wp_remote_get( $url, $args );
                break;

            case 'POST':
                $response = wp_remote_post( $url, $args );
                break;

            default:
                $args['method'] = $method;
                $response = wp_remote_request( $url, $args );
        }

        if ( is_wp_error( $response ) ) {
            $this->log( $source, 'Response Error: ' . $response->get_error_message() );
            throw new Exception( $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        $this->log( $source, 'Response: ' . wp_json_encode( $body ) );

        return array( $code, $body );

    }

    // Generate API signature
    private function generate_api_signature( array $params, $is_fastpay = false ) {
        unset( $params['sign'] );

        if( !$is_fastpay ) {
            foreach ( $params as $key => $value ) {
                if ( is_array( $value ) ) {
                    $this->format_api_signature_array_params( $key, $value );
                }
            }
            $encoded_params = http_build_query( $params );
        } else {
            $encoded_params = json_encode( $params, JSON_UNESCAPED_SLASHES );
        }

        $api_key = wc_get_setting_fpn_by_fave( 'api_key' );

        return hash_hmac( 'sha256', $encoded_params, $api_key );

    }

    // Format array parameters for API signature
    private function format_api_signature_array_params( $parent_key, $params ) {

        $formatted_params = array();

        // Format array to json-like
        // from ['abc'=>'def'] to {'abc'=>'def'}
        foreach ( $params as $key => $value) {
            if ( is_array( $value ) ) {
                $formatted_params[] = '' . $parent_key . '[' . $key . ']=' . $this->format_api_signature_array_params( $value );
            } else {
                $formatted_params[] = '' . $parent_key . '[' . $key . ']=' . $value;
            }
        }

        return $formatted_params;

    }

    // Get IPN response data
    public function get_ipn_response() {

        if ( !in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'POST' ) ) ) {
            $this->log( $source, 'Response Error: ' . $response->get_error_message() );
            return false;
        }

        $data = array_map( 'sanitize_text_field', $_REQUEST );

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $data = file_get_contents( 'php://input' );
            $data = json_decode( $data, true );
        }

        if ( empty( $data ) ) {
            return false;
        }

        if ( !$formatted_data = $this->get_valid_ipn_response( $data ) ) {
            $this->log( $source, 'Invalid matching data : ' . implode('|', $data) );
            return false;
        }

        return $formatted_data;

    }

    // Format IPN response data to only get accepted parameters
    public function get_valid_ipn_response( array $data ) {

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            return $data;
        } else {
            $params = $this->get_redirect_params();
        }

        $allowed_params = array();

        foreach ( $params as $param ) {
            // Return false if required parameters is not passed to the URL
            if ( !isset( $data[ $param ] ) ) {
                return false;
            }

            $allowed_params[ $param ] = $data[ $param ];
        }

        return $allowed_params;

    }

    // Get list of parameters that will be passed in redirect URL
    private function get_redirect_params() {

        return array(
            'receipt_id',
            'omni_reference',
            'status',
            'total_amount_cents',
            'sign',
        );

    }

    // Validate IPN response data
    public function validate_ipn_response( $response ) {

        if ( $response['sign'] !== $this->generate_api_signature( $response ) ) {
            throw new Exception( 'Signature mismatch.' );
        }

        return true;

    }

}
