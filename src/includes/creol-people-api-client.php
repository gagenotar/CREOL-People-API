<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class CREOL_People_API_Client {

    private $api_base_url = 'https://api.creol.ucf.edu/People.asmx/GetData';
    private $transient_prefix;

    public function __construct() {
        $this->transient_prefix = defined( 'CREOL_PEOPLE_API_TRANSIENT_PREFIX' ) ? CREOL_PEOPLE_API_TRANSIENT_PREFIX : 'creol_';
    }

    /**
     * Fetch data from the CREOL People API with caching
     *
     * @param array $params Query parameters for the API request
     * @param int $cache_ttl Cache time-to-live in seconds
     * @return mixed API response data or error message
     */
    public function fetch( $params = array(), $cache_ttl = 300 ) {
        $url = $this->api_base_url . '?' . implode( '&', $params );
        $cache_key = $this->transient_prefix . md5( $url );
        $data = get_transient( $cache_key );

        if ( false === $data ) {
            $response = wp_remote_get( $url, array( 'timeout' => 10 ) );
            if ( is_wp_error( $response ) ) {
                error_log( 'CREOL People API Error: ' . $response->get_error_message() . ' URL: ' . $url );
                return new WP_Error( 'creol_http_error', 'Network error while fetching CREOL People API: ' . $response->get_error_message() );
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            if ( 200 !== $response_code ) {
                error_log( 'CREOL People API Error: Unexpected response code ' . $response_code . ' URL: ' . $url );
                return new WP_Error( 'creol_http_status', 'Unexpected HTTP status ' . $response_code );
            }

            $body = wp_remote_retrieve_body( $response );
            $decoded = json_decode( $body, true );
            if ( null === $decoded || ! isset( $decoded['response'] ) ) {
                error_log( 'CREOL People API Parse Error: Invalid JSON response | URL: ' . $url . ' | Body: ' . substr( $body, 0, 200 ) );
                return new WP_Error( 'creol_parse_error', 'Invalid JSON response from CREOL API' );
            }

            $data = $decoded['response'];
            if ( $cache_ttl > 0 ) {
                set_transient( $cache_key, $data, $cache_ttl );
            }
        }

        if ( empty( $data ) ) {
            return new WP_Error( 'creol_no_data', 'CREOL API returned no data' );
        }

        return $data;
    }
}
