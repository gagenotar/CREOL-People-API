<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class CREOL_People_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_creol_clear_cache', array( $this, 'clear_cache' ) );
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_options_page(
            'CREOL People API Settings',
            'CREOL People API',
            'manage_options',
            'creol-people-api',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'creol_people_api_settings',
            'creol_people_api_options',
            array( $this, 'sanitize_settings' )
        );

        add_settings_section(
            'creol_people_api_general',
            'General Settings',
            array( $this, 'render_general_section' ),
            'creol-people-api'
        );

        add_settings_field(
            'default_cache_ttl',
            'Default Cache Duration (seconds)',
            array( $this, 'render_cache_ttl_field' ),
            'creol-people-api',
            'creol_people_api_general'
        );

        add_settings_field(
            'default_columns',
            'Default Number of Columns',
            array( $this, 'render_columns_field' ),
            'creol-people-api',
            'creol_people_api_general'
        );

        add_settings_field(
            'default_display',
            'Default Display Mode',
            array( $this, 'render_display_field' ),
            'creol-people-api',
            'creol_people_api_general'
        );
    }

    /**
     * Sanitize settings before saving
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        if ( isset( $input['default_cache_ttl'] ) ) {
            $sanitized['default_cache_ttl'] = absint( $input['default_cache_ttl'] );
            if ( $sanitized['default_cache_ttl'] < 60 ) {
                $sanitized['default_cache_ttl'] = 60; // Minimum 1 minute
            }
        }

        if ( isset( $input['default_columns'] ) ) {
            $sanitized['default_columns'] = absint( $input['default_columns'] );
            if ( $sanitized['default_columns'] < 1 ) {
                $sanitized['default_columns'] = 1;
            } elseif ( $sanitized['default_columns'] > 8 ) {
                $sanitized['default_columns'] = 8;
            }
        }

        if ( isset( $input['default_display'] ) ) {
            $sanitized['default_display'] = in_array( $input['default_display'], array( 'card', 'grid' ) ) 
                ? $input['default_display'] 
                : 'card';
        }

        return $sanitized;
    }

    /**
     * Render general section description
     */
    public function render_general_section() {
        echo '<p>Configure default settings for the CREOL People API plugin. These can be overridden in individual shortcodes.</p>';
    }

    /**
     * Render cache TTL field
     */
    public function render_cache_ttl_field() {
        $options = get_option( 'creol_people_api_options' );
        $value = isset( $options['default_cache_ttl'] ) ? $options['default_cache_ttl'] : 300;
        ?>
        <input type="number" 
               name="creol_people_api_options[default_cache_ttl]" 
               value="<?php echo esc_attr( $value ); ?>" 
               min="60" 
               step="60" 
               class="regular-text">
        <p class="description">How long to cache API responses (minimum 60 seconds)</p>
        <?php
    }

    /**
     * Render columns field
     */
    public function render_columns_field() {
        $options = get_option( 'creol_people_api_options' );
        $value = isset( $options['default_columns'] ) ? $options['default_columns'] : 3;
        ?>
        <select name="creol_people_api_options[default_columns]">
            <?php for ( $i = 1; $i <= 8; $i++ ) : ?>
                <option value="<?php echo $i; ?>" <?php selected( $value, $i ); ?>>
                    <?php echo $i; ?>
                </option>
            <?php endfor; ?>
        </select>
        <p class="description">Default number of columns in the grid layout</p>
        <?php
    }

    /**
     * Render display mode field
     */
    public function render_display_field() {
        $options = get_option( 'creol_people_api_options' );
        $value = isset( $options['default_display'] ) ? $options['default_display'] : 'card';
        ?>
        <select name="creol_people_api_options[default_display]">
            <option value="card" <?php selected( $value, 'card' ); ?>>Card (with images)</option>
            <option value="grid" <?php selected( $value, 'grid' ); ?>>Grid (compact, no images)</option>
        </select>
        <p class="description">Default display mode for person cards</p>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Show success message if settings were saved
        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error(
                'creol_people_api_messages',
                'creol_people_api_message',
                'Settings Saved',
                'updated'
            );
        }

        // Show success message if cache was cleared
        if ( isset( $_GET['cache-cleared'] ) ) {
            add_settings_error(
                'creol_people_api_messages',
                'creol_people_api_message',
                'Cache Cleared Successfully',
                'updated'
            );
        }

        settings_errors( 'creol_people_api_messages' );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields( 'creol_people_api_settings' );
                do_settings_sections( 'creol-people-api' );
                submit_button( 'Save Settings' );
                ?>
            </form>

            <hr>

            <h2>Cache Management</h2>
            <p>Clear all cached API responses. Use this if you need to force a refresh of people data.</p>
            
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                <input type="hidden" name="action" value="creol_clear_cache">
                <?php wp_nonce_field( 'creol_clear_cache', 'creol_cache_nonce' ); ?>
                <?php submit_button( 'Clear All Cache', 'secondary', 'submit', false ); ?>
            </form>

            <hr>

            <h2>Shortcode Usage</h2>
            <p>Use the following shortcode to display people on your pages:</p>
            <code>[creol_people grpname1="Faculty"]</code>
            
            <h3>Available Attributes:</h3>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><strong>grpname1</strong>: Primary group filter</li>
                <li><strong>grpname2</strong>: Secondary group filter</li>
                <li><strong>limit</strong>: Maximum number of people to display</li>
                <li><strong>display</strong>: Display mode (card or grid)</li>
                <li><strong>columns</strong>: Number of grid columns (1-8)</li>
                <li><strong>cache_ttl</strong>: Cache duration in seconds</li>
            </ul>

            <h3>Examples:</h3>
            <ul style="list-style: none; margin-left: 20px;">
                <li><code>[creol_people grpname1="Faculty" columns="4"]</code></li>
                <li><code>[creol_people grpname1="Staff" display="grid" limit="10"]</code></li>
                <li><code>[creol_people grpname1="Faculty" grpname2="Research"]</code></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Clear all CREOL cache
     */
    public function clear_cache() {
        // Verify nonce
        if ( ! isset( $_POST['creol_cache_nonce'] ) || 
             ! wp_verify_nonce( $_POST['creol_cache_nonce'], 'creol_clear_cache' ) ) {
            wp_die( 'Security check failed' );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        // Delete all CREOL transients
        global $wpdb;
        $prefix = defined( 'CREOL_PEOPLE_API_TRANSIENT_PREFIX' ) ? CREOL_PEOPLE_API_TRANSIENT_PREFIX : 'creol_';
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_{$prefix}%' 
            OR option_name LIKE '_transient_timeout_{$prefix}%'"
        );

        // Redirect back with success message
        wp_redirect( add_query_arg(
            array(
                'page' => 'creol-people-api',
                'cache-cleared' => '1'
            ),
            admin_url( 'options-general.php' )
        ) );
        exit;
    }
}

new CREOL_People_Admin();
