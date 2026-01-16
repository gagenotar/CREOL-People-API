<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register REST API endpoints for CREOL People API
 */
function creol_register_rest_endpoints() {

    // Handles individual headshot image requests to avoid Chrome Local Network Access issues
    register_rest_route( 'creol-people/v1', '/person-headshot/(?P<id>\d+)', [
        'methods'   => 'GET',
        'callback'  => 'creol_proxy_headshot',
        'permission_callback' => '__return_true', 
    ] );

}
add_action( 'rest_api_init', 'creol_register_rest_endpoints' );

/**
 * Proxy individual headshot image requests to avoid Chrome Local Network Access issues
 *
 * @param WP_REST_Request $request The REST request object
 */
function creol_proxy_headshot( WP_REST_Request $request ) {
    $person_id = absint( $request['id'] );
    if ( ! $person_id ) {
        status_header( 400 );
        exit;
    }

    // Cache which upstream path works for this person
    $path_cache_key = 'creol_headshot_path_' . $person_id;
    $resolved_path  = get_transient( $path_cache_key );

    // Ordered candidate paths
    // CREOL API allows both URIs
    $paths = $resolved_path
        ? [ $resolved_path ]
        : [
            '200x300Portrait',
            'Original',
        ];

    // Try each path until one works
    foreach ( $paths as $path ) {
        $url = sprintf(
            'https://api.creol.ucf.edu/People/images/%s/%d.jpg',
            $path,
            $person_id
        );

        $response = wp_remote_get( $url, [ 'timeout' => 8 ] );

        if ( is_wp_error( $response ) ) {
            continue;
        }

        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
            continue;
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            continue;
        }

        // Set a cache for this working path
        set_transient( $path_cache_key, $path, HOUR_IN_SECONDS * 6 );

        header( 'Content-Type: image/jpeg' );
        header( 'Content-Length: ' . strlen( $body ) );
        header( 'Cache-Control: public, max-age=' . ( HOUR_IN_SECONDS * 6 ) );

        // Output the image and exit to avoid default WP json encoding
        echo $body;
        exit;
    }

    /*
     * FINAL FALLBACK — NoImage
     * Do not cache per-person
     */
    $fallback_url = 'https://api.creol.ucf.edu/People/images/200x300Portrait/NoImage.jpg';
    $fallback     = wp_remote_get( $fallback_url, [ 'timeout' => 8 ] );

    if ( ! is_wp_error( $fallback ) && wp_remote_retrieve_response_code( $fallback ) === 200 ) {
        $body = wp_remote_retrieve_body( $fallback );

        header( 'Content-Type: image/jpeg' );
        header( 'Content-Length: ' . strlen( $body ) );
        header( 'Cache-Control: public, max-age=' . ( HOUR_IN_SECONDS * 6 ) );

        // Output the image and exit to avoid default WP json encoding
        echo $body;
        exit;
    }

    // Absolute worst case
    status_header( 404 );
    exit;
}
