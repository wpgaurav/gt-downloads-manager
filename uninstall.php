<?php

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$table_name = preg_replace('/[^a-zA-Z0-9_]/', '', $wpdb->prefix . 'gtdm_downloads');

if ($table_name) {
    $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
}

delete_option('gtdm_version');
delete_option('gtdm_hard_reset_done');

delete_option('gtdm_caps_version');
delete_option('gtdm_delete_data_on_uninstall');
delete_option('gtdm_settings');

delete_option('gtdm_legacy_version');

$like = $wpdb->esc_like('_transient_gtdm_tr_') . '%';
$timeout_like = $wpdb->esc_like('_transient_timeout_gtdm_tr_') . '%';

$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like));
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $timeout_like));
