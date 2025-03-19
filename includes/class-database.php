<?php
namespace GTDownloadsManager;

class Database {
    public static function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE " . DM_TABLE . " (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            featured_image_id bigint(20) UNSIGNED,
            file_url varchar(255),
            direct_url varchar(255),
            category varchar(100),
            download_count int DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
    
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}