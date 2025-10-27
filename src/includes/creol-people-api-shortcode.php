<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class CREOL_People_Shortcode {

    private $cache_key = 'creol_people_response';
    private $cache_ttl = 300; // seconds

    public function __construct() {
        add_shortcode( 'creol_people_api', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        $base = plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'src/public/';
        
        // Use plugin version constant for cache busting
        $version = defined( 'CREOL_PEOPLE_API_VERSION' ) ? CREOL_PEOPLE_API_VERSION : '1.0.0';
        
        wp_register_style( 'creol-people-style', $base . 'css/style.css', array(), $version );
        wp_enqueue_style( 'creol-people-style' );
        wp_register_script( 'creol-people-script', $base . 'js/script.js', array( 'jquery' ), $version, true );
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
        // Get saved options for defaults
        $saved_options = get_option( 'creol_people_api_options', array() );
        
        // normalize attributes (support different casing/keys)
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $defaults = array(
            'grpname1' => '',
            'grpname2' => '',
            'included_positions' => '',
            'excluded_positions' => '',
            'limit' => 0,
            'cache_ttl' => isset( $saved_options['default_cache_ttl'] ) ? $saved_options['default_cache_ttl'] : $this->cache_ttl,
            'display' => isset( $saved_options['default_display'] ) ? $saved_options['default_display'] : 'card',
            'columns' => isset( $saved_options['default_columns'] ) ? $saved_options['default_columns'] : 3,
            'dark_mode' => isset( $saved_options['default_dark_mode'] ) ? $saved_options['default_dark_mode'] : 0,
        );
        $atts = wp_parse_args( $atts, $defaults );

        $grp1 = sanitize_text_field( $atts['grpname1'] );
        $grp2 = sanitize_text_field( $atts['grpname2'] );
        $included_positions = sanitize_text_field( $atts['included_positions'] );
        $excluded_positions = sanitize_text_field( $atts['excluded_positions'] );
        $display = sanitize_text_field( $atts['display'] );
        $columns = intval( $atts['columns'] );
        // Clamp columns to 1..8
        if ( $columns < 1 ) {
            $columns = 1;
        } elseif ( $columns > 8 ) {
            $columns = 8;
        }
        $limit = intval( $atts['limit'] );
        $cache_ttl = intval( $atts['cache_ttl'] );
        $dark = intval( $atts['dark_mode'] );

        // Build API params
        $params = array( 'WWWPeople' );
        if ( $grp1 !== '' ) {
            $params[] = 'GrpName1=' . rawurlencode( $grp1 );
        }
        if ( $grp2 !== '' ) {
            $params[] = 'GrpName2=' . rawurlencode( $grp2 );
        }
        if ( $included_positions !== '' ) {
            $params[] = 'IncludedPositions=' . rawurlencode( $included_positions );
        }
        if ( $excluded_positions !== '' ) {
            $params[] = 'ExcludedPositions=' . rawurlencode( $excluded_positions );
        }
        $url = $base . '?' . implode( '&', $params );

        $client = new CREOL_People_API_Client();
        $data = $client->fetch( $params, $cache_ttl );

        if ( is_wp_error( $data ) ) {
            error_log( 'CREOL People API Error: ' . $data->get_error_message() . ' | URL: ' . $url );
            return '<div class="creol-people-error">Could not retrieve people data.</div>';
        }

        if ( empty( $data ) ) {
            return '<div class="creol-people-empty">No people found.</div>';
        }

        // Filter out invalid person data
        $data = array_filter( $data, array( $this, 'validate_person_data' ) );
        
        if ( empty( $data ) ) {
            error_log( 'CREOL People API: All person records failed validation | URL: ' . $url );
            return '<div class="creol-people-empty">No valid people data found.</div>';
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
        if ( $dark ) {
            $container_class .= ' creol-people-dark';
        }
        // set CSS variable for columns so CSS can adapt across modes
        $out = '<div class="' . esc_attr( $container_class ) . '" style="--creol-columns:' . esc_attr( $columns ) . '" role="list" aria-label="People directory">';
        foreach ( $data as $person ) {
            $out .= $this->render_card( $person, $display );
        }
        $out .= '</div>';

        return $out;
    }

    /**
     * Validate person data structure
     *
     * @param array $person Person data from API
     * @return bool True if person data is valid
     */
    private function validate_person_data( $person ) {
        if ( ! is_array( $person ) ) {
            return false;
        }
        
        // At minimum, we need either a first or last name
        $has_name = ( 
            ( isset( $person['FirstName'] ) && ! empty( $person['FirstName'] ) ) ||
            ( isset( $person['LastName'] ) && ! empty( $person['LastName'] ) )
        );
        
        return $has_name;
    }

    private function render_card( $person, $display = 'card' ) {
        // Validate person data before rendering
        if ( ! $this->validate_person_data( $person ) ) {
            error_log( 'CREOL People API: Invalid person data structure - ' . print_r( $person, true ) );
            return '';
        }
        
        $image = isset( $person['ImageURL'] ) ? esc_url( $person['ImageURL'] ) : '';
        $first = isset( $person['FirstName'] ) ? sanitize_text_field( wp_strip_all_tags( $person['FirstName'] ) ) : '';
        $last = isset( $person['LastName'] ) ? sanitize_text_field( wp_strip_all_tags( $person['LastName'] ) ) : '';
        $name = trim( $first . ' ' . $last );
        $position = isset( $person['Position'] ) ? wp_kses_post( $person['Position'] ) : '';
        $email = isset( $person['Email'] ) ? sanitize_email( $person['Email'] ) : '';
        $phone = isset( $person['Phone'] ) ? sanitize_text_field( wp_strip_all_tags( $person['Phone'] ) ) : '';
        $room = isset( $person['Room'] ) ? sanitize_text_field( wp_strip_all_tags( $person['Room'] ) ) : '';

        $out = '<article class="creol-person-card align-items-start" role="listitem" itemscope itemtype="https://schema.org/Person">';
        // In 'grid' display mode we exclude the image to show a compact grid of info
        if ( 'card' === $display && $image ) {
            $out .= '<div class="creol-person-image"><img src="' . $image . '" alt="' . esc_attr( $name ) . '" itemprop="image"></div>';
        }
        $out .= '<div class="creol-person-body align-items-start">';
        $out .= '<h3 class="creol-person-name text-center" itemprop="name">' . esc_html( $name ) . '</h3>';
        if ( $position ) {
            $out .= '<div class="creol-person-position text-center" itemprop="jobTitle">' . $position . '</div>';
        }
        if ( $room ) {
            $out .= '<div class="creol-person-room text-center"><span class="screen-reader-text">Room: </span>' . esc_html( $room ) . '</div>';
        }
        if ( $phone ) {
            $out .= '<div class="creol-person-phone text-center" itemprop="telephone"><span class="screen-reader-text">Phone: </span>' . esc_html( $phone ) . '</div>';
        }
        if ( $email ) {
            $out .= '<div class="creol-person-email text-center"><a href="mailto:' . esc_attr( $email ) . '" itemprop="email" aria-label="Email ' . esc_attr( $name ) . '">Email</a></div>';
        }
        $out .= '</div>'; // body
        $out .= '</article>'; // card

        return $out;
    }
}

new CREOL_People_Shortcode();
