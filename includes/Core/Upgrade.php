<?php

declare(strict_types=1);

namespace GTDownloadsManager\Core;

use GTDownloadsManager\Domain\DownloadRepository;

class Upgrade {
    private const OPTION_VERSION = 'gtdm_version';
    private const OPTION_RESET_DONE = 'gtdm_hard_reset_done';

    public static function maybe_upgrade(): void {
        DownloadRepository::create_table();

        $stored_version = (string) get_option(self::OPTION_VERSION, '0.0.0');

        if (version_compare($stored_version, GTDM_VERSION, '>=')) {
            return;
        }

        self::run_hard_reset();

        update_option(self::OPTION_VERSION, GTDM_VERSION, false);
    }

    public static function run_hard_reset(): void {
        if ((int) get_option(self::OPTION_RESET_DONE, 0) === 1) {
            return;
        }

        global $wpdb;

        $legacy_table = self::sanitize_table_name($wpdb->prefix . 'gtdownloads_manager');
        $wpdb->query("DROP TABLE IF EXISTS `{$legacy_table}`");

        delete_option('gtdm_delete_data_on_uninstall');
        delete_option('gtdm_settings');
        delete_option('gtdm_legacy_version');

        DownloadRepository::create_table();

        update_option(self::OPTION_RESET_DONE, 1, false);
    }

    private static function sanitize_table_name(string $table_name): string {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $table_name) ?: 'wp_gtdownloads_manager';
    }
}
