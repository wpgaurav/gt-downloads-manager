<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get the deletion preference
$delete_data = get_option('gtdm_delete_data_on_uninstall', false);

// Only delete data if explicitly requested by the user
if ($delete_data) {
    global $wpdb;
    
    // Delete the custom table
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}gtdownloads_manager");
    
    // Delete all plugin options
    delete_option('gtdm_delete_data_on_uninstall');
    delete_option('gtdm_settings');
}