<?php
/*
Plugin Name: CREOL People API
Description: Display people in a grid layout using data from the CREOL API.
Version: 1.0.0
Author: UCF Web Communications
License: GPL3
GitHub Plugin URI: 
*/

if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'CREOL_PEOPLE_API_VERSION', '1.0.0' );
define( 'CREOL_PEOPLE_API_PATH', plugin_dir_path( __FILE__ ) );
define( 'CREOL_PEOPLE_API_URL', plugin_dir_url( __FILE__ ) );
define( 'CREOL_PEOPLE_API_TRANSIENT_PREFIX', 'creol_' );

// Load the API client class
require_once plugin_dir_path( __FILE__ ) . 'src/includes/creol-people-api-client.php';

// Load the shortcode class
require_once plugin_dir_path( __FILE__ ) . 'src/includes/creol-people-api-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'src/includes/creol-alumni-api-shortcode.php';

// Load the admin class
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'src/includes/creol-people-api-admin.php';
}
