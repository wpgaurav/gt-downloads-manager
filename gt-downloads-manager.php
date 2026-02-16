<?php
/*
Plugin Name: GT Downloads Manager
Description: Manage and showcase downloadable resources with a dedicated custom table, REST API, and dynamic blocks.
Version: 2.0.0
Author: Gaurav Tiwari
Text Domain: gt-downloads-manager
Requires at least: 6.4
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('GTDM_VERSION')) {
    define('GTDM_VERSION', '2.0.0');
}

if (! defined('GTDM_PLUGIN_FILE')) {
    define('GTDM_PLUGIN_FILE', __FILE__);
}

if (! defined('GTDM_PLUGIN_BASENAME')) {
    define('GTDM_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

if (! defined('GTDM_PATH')) {
    define('GTDM_PATH', plugin_dir_path(__FILE__));
}

if (! defined('GTDM_URL')) {
    define('GTDM_URL', plugin_dir_url(__FILE__));
}

require_once GTDM_PATH . 'includes/Core/Requirements.php';
require_once GTDM_PATH . 'includes/Core/Upgrade.php';
require_once GTDM_PATH . 'includes/Core/Activator.php';
require_once GTDM_PATH . 'includes/Core/Plugin.php';

require_once GTDM_PATH . 'includes/Domain/DownloadRepository.php';
require_once GTDM_PATH . 'includes/Domain/QueryService.php';
require_once GTDM_PATH . 'includes/Domain/DownloadService.php';

require_once GTDM_PATH . 'includes/Frontend/Assets.php';
require_once GTDM_PATH . 'includes/Frontend/Renderer.php';
require_once GTDM_PATH . 'includes/Frontend/Shortcodes.php';
require_once GTDM_PATH . 'includes/Frontend/DownloadController.php';

require_once GTDM_PATH . 'includes/Rest/Api.php';

require_once GTDM_PATH . 'includes/Admin/Admin.php';

require_once GTDM_PATH . 'includes/Blocks/Manager.php';

register_activation_hook(__FILE__, ['GTDownloadsManager\\Core\\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['GTDownloadsManager\\Core\\Activator', 'deactivate']);

if (! GTDownloadsManager\Core\Requirements::maybe_self_deactivate()) {
    return;
}

add_action('plugins_loaded', ['GTDownloadsManager\\Core\\Plugin', 'boot']);
