<?php
/**
 * Plugin Name: CREOL People Shortcode
 * Description: Shortcode [creol_people] to display people cards fetched from CREOL public API.
 * Version: 0.1.0
 * Author: Generated
 * Text Domain: creol-people-shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class CREOL_People_Shortcode {

    private $cache_key = 'creol_people_response';
    private $cache_ttl = 300; // seconds

    public function __construct() {
        add_shortcode( 'creol_people', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        $base = plugin_dir_url( __FILE__ );
        wp_register_style( 'creol-people-style', $base . 'css/style.css', array(), '0.1.0' );
        wp_enqueue_style( 'creol-people-style' );
        wp_register_script( 'creol-people-script', $base . 'js/script.js', array( 'jquery' ), '0.1.0', true );
        wp_enqueue_script( 'creol-people-script' );
    }

    /**
     * Shortcode handler
     * Attributes (case-insensitive accepted):
     * - GrpName1 or grpname1 or grp1: first group name
     * - GrpName2 or grpname2 or grp2: optional second group name
     * - limit: number of entries to show (0 = all)
     * - cache_ttl: override default cache seconds
     */
    public function render_shortcode( $atts ) {
        // normalize attributes (support different casing/keys)
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $defaults = array(
            'grpname1' => '',
            'grpname2' => '',
            'limit' => 0,
            'cache_ttl' => $this->cache_ttl,
            'display' => 'card', // 'card' or 'grid' (future modes allowed)
            'columns' => 3, // number of columns for grid layouts (1-6)
        );
        $atts = wp_parse_args( $atts, $defaults );

        $grp1 = sanitize_text_field( $atts['grpname1'] );
        $grp2 = sanitize_text_field( $atts['grpname2'] );
        $display = sanitize_text_field( $atts['display'] );
        $columns = intval( $atts['columns'] );
        // Clamp columns to 1..6
        if ( $columns < 1 ) {
            $columns = 1;
        } elseif ( $columns > 6 ) {
            $columns = 6;
        }
        $limit = intval( $atts['limit'] );
        $cache_ttl = intval( $atts['cache_ttl'] );

        // Build API URL
        $base = 'https://api.creol.ucf.edu/People.asmx/GetData';
        // API expects 'WWWPeople' then GrpName1/GrpName2 parameters
        $params = array( 'WWWPeople' );
        if ( $grp1 !== '' ) {
            $params[] = 'GrpName1=' . rawurlencode( $grp1 );
        }
        if ( $grp2 !== '' ) {
            $params[] = 'GrpName2=' . rawurlencode( $grp2 );
        }
        $url = $base . '?' . implode( '&', $params );

    // Use transient caching keyed by URL (include group params)
    $transient_key = 'creol_' . md5( $url );
        $data = get_transient( $transient_key );
        if ( false === $data ) {
            $response = wp_remote_get( $url, array( 'timeout' => 10 ) );
            if ( is_wp_error( $response ) ) {
                return '<div class="creol-people-error">Could not retrieve people data.</div>';
            }
            $body = wp_remote_retrieve_body( $response );
            $decoded = json_decode( $body, true );
            if ( null === $decoded || ! isset( $decoded['response'] ) ) {
                return '<div class="creol-people-error">API returned unexpected data.</div>';
            }
            $data = $decoded['response'];
            set_transient( $transient_key, $data, $cache_ttl );
        }

        if ( empty( $data ) ) {
            return '<div class="creol-people-empty">No people found.</div>';
        }

        // Limit results if requested
        if ( $limit > 0 ) {
            $data = array_slice( $data, 0, $limit );
        }

        // Build HTML
        // Top-level container class varies by display mode
        $container_class = 'creol-people-grid';
        if ( 'grid' === $display ) {
            $container_class .= ' creol-people-grid-mode';
        }
        // set CSS variable for columns so CSS can adapt across modes
        $out = '<div class="' . esc_attr( $container_class ) . '" style="--creol-columns:' . esc_attr( $columns ) . '">';
        foreach ( $data as $person ) {
            $out .= $this->render_card( $person, $display );
        }
        $out .= '</div>';

        return $out;
    }

    private function render_card( $person, $display = 'card' ) {
        $image = isset( $person['ImageURL'] ) ? esc_url( $person['ImageURL'] ) : '';
        $first = isset( $person['FirstName'] ) ? sanitize_text_field( wp_strip_all_tags( $person['FirstName'] ) ) : '';
        $last = isset( $person['LastName'] ) ? sanitize_text_field( wp_strip_all_tags( $person['LastName'] ) ) : '';
        $name = trim( $first . ' ' . $last );
        $position = isset( $person['Position'] ) ? wp_kses_post( $person['Position'] ) : '';
        $email = isset( $person['Email'] ) ? sanitize_email( $person['Email'] ) : '';
        $phone = isset( $person['Phone'] ) ? sanitize_text_field( wp_strip_all_tags( $person['Phone'] ) ) : '';
        $room = isset( $person['Room'] ) ? sanitize_text_field( wp_strip_all_tags( $person['Room'] ) ) : '';

        $out = '<div class="creol-person-card">';
        // In 'grid' display mode we exclude the image to show a compact grid of info
        if ( 'card' === $display && $image ) {
            $out .= '<div class="creol-person-image"><img src="' . $image . '" alt="' . esc_attr( $name ) . '"></div>';
        }
        $out .= '<div class="creol-person-body">';
        $out .= '<h3 class="creol-person-name">' . esc_html( $name ) . '</h3>';
        if ( $position ) {
            $out .= '<div class="creol-person-position">' . $position . '</div>';
        }
        if ( $email ) {
            $out .= '<div class="creol-person-email"><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></div>';
        }
        if ( $phone ) {
            $out .= '<div class="creol-person-phone">' . esc_html( $phone ) . '</div>';
        }
        if ( $room ) {
            $out .= '<div class="creol-person-room">' . esc_html( $room ) . '</div>';
        }
        $out .= '</div>'; // body
        $out .= '</div>'; // card

        return $out;
    }
}

new CREOL_People_Shortcode();
