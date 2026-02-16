<?php

declare(strict_types=1);

namespace GTDownloadsManager\Core;

use GTDownloadsManager\Domain\DownloadRepository;
use GTDownloadsManager\Frontend\DownloadController;

class Activator {
    public static function activate(): void {
        if (! Requirements::is_compatible()) {
            if (! function_exists('deactivate_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            deactivate_plugins(GTDM_PLUGIN_BASENAME);
            wp_die(
                esc_html(Requirements::get_failure_message()),
                esc_html__('Plugin Activation Error', 'gt-downloads-manager'),
                ['back_link' => true]
            );
        }

        DownloadRepository::create_table();
        DownloadController::register_rewrite();
        Upgrade::run_hard_reset();

        update_option('gtdm_version', GTDM_VERSION, false);

        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}
