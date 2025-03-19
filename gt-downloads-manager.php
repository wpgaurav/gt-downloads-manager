<?php
/*
Plugin Name: GT Downloads Manager
Description: Manage downloadable resources
Version: 1.0
Author: Gaurav Tiwari
Text Domain: gtdownloads-manager
*/

declare(strict_types=1);

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('DM_VERSION', '1.0');
define('DM_TABLE', $GLOBALS['wpdb']->prefix . 'gtdownloads_manager');
define('DM_PATH', plugin_dir_path(__FILE__));
define('DM_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once DM_PATH . 'includes/class-database.php';
require_once DM_PATH . 'includes/class-shortcodes.php';
require_once DM_PATH . 'includes/class-downloads.php';
require_once DM_PATH . 'includes/class-admin.php';
require_once DM_PATH . 'includes/class-widget.php';
require_once DM_PATH . 'includes/class-settings.php';

register_activation_hook(__FILE__, ['GTDownloadsManager\Database', 'create_table']);

// Add in the plugins_loaded callback
add_action('plugins_loaded', function() {
    GTDownloadsManager\Downloads::instance();
    GTDownloadsManager\Shortcodes::instance();
    
    // Initialize admin interface only in admin area
    if (is_admin()) {
        GTDownloadsManager\Admin::instance();
        GTDownloadsManager\Settings::instance();
    }
    
    // Register widget
    add_action('widgets_init', function() {
        register_widget('GTDownloadsManager\Widget');
    });
    
    // Add download handler
    add_action('init', function() {
        if (isset($_GET['dm_download']) && !empty($_GET['nonce'])) {
            $download_id = intval($_GET['dm_download']);
            
            if (wp_verify_nonce($_GET['nonce'], 'download-' . $download_id)) {
                $downloads = GTDownloadsManager\Downloads::instance();
                $download_data = $downloads->get_downloads(['id' => $download_id]);
                
                if (!empty($download_data)) {
                    $download = (array)$download_data[0];
                    
                    // Track this download
                    $downloads->track_download($download_id);
                    
                    // Determine which URL to use
                    $file_url = '';
                    if (!empty($download['direct_url'])) {
                        $file_url = $download['direct_url'];
                        wp_redirect($file_url);
                        exit;
                    } elseif (!empty($download['file_url'])) {
                        $file_url = wp_get_attachment_url($download['file_url']);
                        if ($file_url) {
                            // Set appropriate headers for download
                            $file_path = get_attached_file($download['file_url']);
                            if (file_exists($file_path)) {
                                header('Content-Description: File Transfer');
                                header('Content-Type: application/octet-stream');
                                header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
                                header('Expires: 0');
                                header('Cache-Control: must-revalidate');
                                header('Pragma: public');
                                header('Content-Length: ' . filesize($file_path));
                                flush();
                                readfile($file_path);
                                exit;
                            } else {
                                wp_redirect($file_url);
                                exit;
                            }
                        }
                    }
                }
            }
        }
    });
});