<?php

declare(strict_types=1);

namespace GTDownloadsManager\Core;

class Requirements {
    private const MIN_PHP = '8.1';
    private const MIN_WP = '6.4';

    public static function is_compatible(): bool {
        global $wp_version;

        return version_compare(PHP_VERSION, self::MIN_PHP, '>=')
            && version_compare((string) $wp_version, self::MIN_WP, '>=');
    }

    public static function maybe_self_deactivate(): bool {
        if (self::is_compatible()) {
            return true;
        }

        if (! is_admin()) {
            return false;
        }

        if (! function_exists('deactivate_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (function_exists('is_plugin_active') && is_plugin_active(GTDM_PLUGIN_BASENAME)) {
            deactivate_plugins(GTDM_PLUGIN_BASENAME);
        }

        add_action('admin_notices', [self::class, 'render_admin_notice']);

        return false;
    }

    public static function render_admin_notice(): void {
        $message = self::get_failure_message();

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html($message)
        );
    }

    public static function get_failure_message(): string {
        global $wp_version;

        return sprintf(
            /* translators: 1: minimum PHP version, 2: current PHP version, 3: minimum WordPress version, 4: current WordPress version */
            __('GT Downloads Manager requires PHP %1$s+ (current: %2$s) and WordPress %3$s+ (current: %4$s).', 'gt-downloads-manager'),
            self::MIN_PHP,
            PHP_VERSION,
            self::MIN_WP,
            (string) $wp_version
        );
    }
}
