<?php

declare(strict_types=1);

namespace GTDownloadsManager\Core;

use GTDownloadsManager\Admin\Admin;
use GTDownloadsManager\Blocks\Manager as BlockManager;
use GTDownloadsManager\Frontend\Assets;
use GTDownloadsManager\Frontend\DownloadController;
use GTDownloadsManager\Frontend\Shortcodes;
use GTDownloadsManager\Rest\Api;

class Plugin {
    private static bool $booted = false;

    public static function boot(): void {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        load_plugin_textdomain('gt-downloads-manager', false, dirname(GTDM_PLUGIN_BASENAME) . '/languages');

        Upgrade::maybe_upgrade();

        Assets::init();
        DownloadController::init();
        Shortcodes::init();
        Api::init();
        BlockManager::init();

        if (is_admin()) {
            Admin::init();
        }
    }
}
