<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class CREOL_Alumni_Shortcode {

    private $cache_key = 'creol_alumni_response';
    private $cache_ttl = 300; // seconds

    public function __construct() {
        add_shortcode( 'creol_alumni_api', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        $base = plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'src/public/';
        
        // Use plugin version constant for cache busting
        $version = defined( 'CREOL_PEOPLE_API_VERSION' ) ? CREOL_PEOPLE_API_VERSION : '1.0.0';

        wp_register_style( 'creol-alumni-style', $base . 'css/style.css', array(), $version );
        wp_enqueue_style( 'creol-alumni-style' );
        wp_register_script( 'creol-alumni-script', $base . 'js/script.js', array( 'jquery' ), $version, true );
        wp_enqueue_script( 'creol-alumni-script' );
    }

    /**
     * Shortcode handler
     * Attributes (case-insensitive accepted):
     * - year: graduation year
     * - degree: (all, ms, phd)
     * - limit: max number of results to show
     * - display: (table, grid)
     * - columns: number of columns for grid display (1-8)
     * - dark_mode: 1 to enable dark mode styling
     * - cache_ttl: override default cache seconds
     */
    public function render_shortcode( $atts ) {
        // Get saved options for defaults
        $saved_options = get_option( 'creol_alumni_api_options', array() );
        
        // normalize attributes (support different casing/keys)
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $defaults = array(
            'year' => '',
            'degree' => 'all',
            'limit' => 0,
            'cache_ttl' => isset( $saved_options['default_cache_ttl'] ) ? $saved_options['default_cache_ttl'] : $this->cache_ttl,
            'display' => isset( $saved_options['default_display'] ) ? $saved_options['default_display'] : 'grid',
            'columns' => isset( $saved_options['default_columns'] ) ? $saved_options['default_columns'] : 3,
            'dark_mode' => isset( $saved_options['default_dark_mode'] ) ? $saved_options['default_dark_mode'] : 0,
        );
        $atts = wp_parse_args( $atts, $defaults );

        $year = sanitize_text_field( $atts['year'] );
        $degree = sanitize_text_field( $atts['degree'] );
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
        $params = array( 'WWWAlumni' );
        if ( $year !== '' ) {
            $params[] = 'Year=' . rawurlencode( $year );
        }
        if ( $degree !== '' ) {
            $params[] = 'Degree=' . rawurlencode( $degree );
        }
        $url = $base . '?' . implode( '&', $params );

        $client = new CREOL_People_API_Client();
        $data = $client->fetch( $params, $cache_ttl );

        if ( is_wp_error( $data ) ) {
            error_log( 'CREOL People API Error: ' . $data->get_error_message() . ' | URL: ' . $url );
            return '<div class="creol-people-error">Could not retrieve people data.</div>';
        }

        if ( empty( $data ) ) {
            return '<div class="creol-alumni-empty">No alumni found.</div>';
        }

        // Filter out invalid person data
        $data = array_filter( $data, array( $this, 'validate_person_data' ) );
        
        if ( empty( $data ) ) {
            error_log( 'CREOL Alumni API: All alumni records failed validation | URL: ' . $url );
            return '<div class="creol-alumni-empty">No valid alumni data found.</div>';
        }

        // Limit results if requested
        if ( $limit > 0 ) {
            $data = array_slice( $data, 0, $limit );
        }

        // Build HTML
        // Top-level container class varies by display mode
        $container_class = 'creol-alumni-grid';
        if ( 'table' === $display ) {
            $container_class .= ' creol-alumni-table-mode';
        } else {
            $container_class .= ' creol-alumni-grid-mode';
        }
        if ( $dark ) {
            $container_class .= ' creol-alumni-dark';
        }

        if ( 'table' === $display ) {
            return $this->render_table( $data );
        }

        // set CSS variable for columns so CSS can adapt across modes
        $out = '<div class="' . esc_attr( $container_class ) . '" style="--columns: ' . esc_attr( $columns ) . ';" role="list" aria-label="Alumni directory">';
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
        
        // At minimum, we need a FirstLastName, a Program, a Semester, and a Degree
        $has_valid_fields = ( 
            ( isset( $person['FirstLastName'] ) && ! empty( $person['FirstLastName'] ) ) &&
            ( isset( $person['Program'] ) && ! empty( $person['Program'] ) ) &&
            ( isset( $person['Semester'] ) && ! empty( $person['Semester'] ) ) &&
            ( isset( $person['Degree'] ) && ! empty( $person['Degree'] ) )
        );

        return $has_valid_fields;
    }

    private function render_table( $data ) {
        $out = '<div class="creol-alumni-table-wrapper" role="region" aria-label="Alumni table">';
        $out .= '<table class="creol-alumni-table" role="table" aria-label="Alumni: name, program, semester, and advisor.">';
        $out .= '<thead class="creol-alumni-table-head">';
        $out .= '<tr class="creol-alumni-table-row" role="row">';
        $out .= '<th scope="col">Name</th>';
        $out .= '<th scope="col">Program</th>';
        $out .= '<th scope="col">Semester</th>';
        $out .= '<th scope="col">Advisor</th>';
        $out .= '</tr>';
        $out .= '</thead>';
        $out .= '<tbody class="creol-alumni-table-body">';
        
        foreach ( $data as $person ) {
            // Validate person data before rendering
            if ( ! $this->validate_person_data( $person ) ) {
                error_log( 'CREOL Alumni API: Invalid person data structure - ' . print_r( $person, true ) );
                continue;
            }

            $name = isset( $person['FirstLastName'] ) ? sanitize_text_field( wp_strip_all_tags( $person['FirstLastName'] ) ) : '';
            $program = isset( $person['Program'] ) ? sanitize_text_field( wp_strip_all_tags( $person['Program'] ) ) : '';
            $semester = isset( $person['Semester'] ) ? sanitize_text_field( wp_strip_all_tags( $person['Semester'] ) ) : '';
            $advisor = isset( $person['AdvisorName'] ) ? sanitize_text_field( wp_strip_all_tags( $person['AdvisorName'] ) ) : '';

            $out .= '<tr class="creol-alumni-table-row" role="row">';
            $out .= '<td class="creol-alumni-table-cell" role="cell" data-label="Name">' . esc_html( $name ) . '</td>';
            $out .= '<td class="creol-alumni-table-cell" role="cell" data-label="Program">' . esc_html( $program ) . '</td>';
            $out .= '<td class="creol-alumni-table-cell" role="cell" data-label="Semester">' . esc_html( $semester ) . '</td>';
            $out .= '<td class="creol-alumni-table-cell" role="cell" data-label="Advisor">' . esc_html( $advisor ) . '</td>';
            $out .= '</tr>';
        }

        $out .= '</tbody></table></div>'; 

        return $out;
    }

    private function render_card( $person, $display = 'card' ) {
        // Validate person data before rendering
        if ( ! $this->validate_person_data( $person ) ) {
            error_log( 'CREOL Alumni API: Invalid person data structure - ' . print_r( $person, true ) );
            return '';
        }
        
        $name = isset( $person['FirstLastName'] ) ? sanitize_text_field( wp_strip_all_tags( $person['FirstLastName'] ) ) : '';
        $program = isset( $person['Program'] ) ? sanitize_text_field( wp_strip_all_tags( $person['Program'] ) ) : '';
        $semester = isset( $person['Semester'] ) ? sanitize_text_field( wp_strip_all_tags( $person['Semester'] ) ) : '';
        $degree = isset( $person['Degree'] ) ? sanitize_text_field( wp_strip_all_tags( $person['Degree'] ) ) : '';
        $advisor = isset( $person['AdvisorName'] ) ? sanitize_text_field( wp_strip_all_tags( $person['AdvisorName'] ) ) : '';

        $out = '<article class="creol-alumni-card align-items-start" role="listitem" itemscope itemtype="https://schema.org/Person">';
        $out .= '<div class="creol-alumni-body align-items-start">';
        $out .= '<h3 class="creol-alumni-name text-center" itemprop="name">' . esc_html( $name ) . '</h3>';
        if ( $program ) {
            $out .= '<div class="creol-alumni-program text-center" itemprop="alumniOf">' . $program . '</div>';
        }
        if ( $semester ) {
            $out .= '<div class="creol-alumni-semester text-center">' . $semester . '</div>';
        }
        if ( $degree ) {
            $out .= '<div class="creol-alumni-degree text-center">' . $degree . '</div>';
        }
        if ( $advisor ) {
            $out .= '<div class="creol-alumni-advisor text-center">Advisor: ' . $advisor . '</div>';
        }
        $out .= '</div>'; // body
        $out .= '</article>'; // card

        return $out;
    }
}

new CREOL_Alumni_Shortcode();
