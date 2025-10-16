<?php
/**
 * Uninstall CREOL People API Plugin
 *
 * This file runs when the plugin is uninstalled (deleted).
 * It cleans up all plugin data from the database.
 *
 * @package CREOL_People_API
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Delete all transients created by this plugin
 *
 * Transients are stored with keys like 'creol_' + md5(url)
 * We need to query the database to find and remove them all.
 */
global $wpdb;

// Delete all transients that start with 'creol_'
// Transients are stored in wp_options with _transient_ prefix
$wpdb->query(
    "DELETE FROM {$wpdb->options} 
    WHERE option_name LIKE '_transient_creol_%' 
    OR option_name LIKE '_transient_timeout_creol_%'"
);

// For multisite installations, clean up for all sites
if ( is_multisite() ) {
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
    
    foreach ( $blog_ids as $blog_id ) {
        switch_to_blog( $blog_id );
        
        // Delete transients for this site
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_creol_%' 
            OR option_name LIKE '_transient_timeout_creol_%'"
        );
        
        restore_current_blog();
    }
}

// Clear any cached data
wp_cache_flush();
