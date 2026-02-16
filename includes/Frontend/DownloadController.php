<?php

declare(strict_types=1);

namespace GTDownloadsManager\Frontend;

use GTDownloadsManager\Domain\DownloadRepository;
use GTDownloadsManager\Domain\DownloadService;

class DownloadController {
    public static function init(): void {
        add_action('init', [self::class, 'register_rewrite']);
        add_filter('query_vars', [self::class, 'add_query_vars']);
        add_action('template_redirect', [self::class, 'handle_download_request']);
    }

    public static function register_rewrite(): void {
        add_rewrite_tag('%gtdm_download_id%', '([0-9]+)');
        add_rewrite_rule('^gtdm-download/([0-9]+)/?$', 'index.php?gtdm_download_id=$matches[1]', 'top');
    }

    /**
     * @param array<int, string> $vars
     * @return array<int, string>
     */
    public static function add_query_vars(array $vars): array {
        $vars[] = 'gtdm_download_id';
        $vars[] = 'gtdm_s';
        $vars[] = 'gtdm_cat';
        $vars[] = 'gtdm_tag';
        $vars[] = 'gtdm_sort';
        $vars[] = 'gtdm_page';

        return $vars;
    }

    public static function handle_download_request(): void {
        $download_id = absint(get_query_var('gtdm_download_id'));

        if ($download_id <= 0 && isset($_GET['gtdm_download_id'])) {
            $download_id = absint(wp_unslash($_GET['gtdm_download_id']));
        }

        if ($download_id <= 0) {
            return;
        }

        $download = DownloadRepository::find($download_id);

        if (! is_array($download) || ($download['status'] ?? '') !== 'publish') {
            status_header(404);
            nocache_headers();
            exit;
        }

        $destination = DownloadService::resolve_destination($download_id);

        if ($destination === '') {
            status_header(404);
            wp_die(
                esc_html__('This download is currently unavailable.', 'gt-downloads-manager'),
                esc_html__('Download unavailable', 'gt-downloads-manager'),
                ['response' => 404]
            );
        }

        DownloadService::track_download($download_id);

        wp_redirect($destination, 302, 'GT Downloads Manager');
        exit;
    }
}
