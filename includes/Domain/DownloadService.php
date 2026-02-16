<?php

declare(strict_types=1);

namespace GTDownloadsManager\Domain;

class DownloadService {
    private const THROTTLE_WINDOW = 600;

    public static function get_download_url(int $download_id): string {
        $download_id = absint($download_id);

        if ($download_id <= 0) {
            return '';
        }

        if ((string) get_option('permalink_structure') !== '') {
            return home_url(user_trailingslashit('gtdm-download/' . $download_id));
        }

        return add_query_arg('gtdm_download_id', (string) $download_id, home_url('/'));
    }

    public static function resolve_destination(int $download_id): string {
        $download = DownloadRepository::find($download_id);

        if (! is_array($download) || ($download['status'] ?? '') !== 'publish') {
            return '';
        }

        $source = isset($download['file_source']) ? sanitize_key((string) $download['file_source']) : 'media';

        if ($source === 'direct') {
            return isset($download['direct_url']) ? esc_url_raw((string) $download['direct_url']) : '';
        }

        $attachment_id = isset($download['file_id']) ? absint($download['file_id']) : 0;

        if ($attachment_id > 0) {
            $url = wp_get_attachment_url($attachment_id);

            return $url ? esc_url_raw($url) : '';
        }

        return '';
    }

    public static function track_download(int $download_id): bool {
        $download = DownloadRepository::find($download_id);

        if (! is_array($download) || ($download['status'] ?? '') !== 'publish') {
            return false;
        }

        if (self::should_throttle($download_id)) {
            return false;
        }

        return DownloadRepository::increment_download_count($download_id);
    }

    /**
     * @param array<string, mixed> $download
     * @return array<string, mixed>
     */
    public static function get_download_payload(array $download): array {
        $download_id = isset($download['id']) ? (int) $download['id'] : 0;

        $description = isset($download['description']) ? (string) $download['description'] : '';
        $excerpt = isset($download['excerpt']) ? (string) $download['excerpt'] : '';

        if ($excerpt === '') {
            $excerpt = wp_trim_words(wp_strip_all_tags($description), 32);
        }

        $featured_image_id = isset($download['featured_image_id']) ? (int) $download['featured_image_id'] : 0;

        return [
            'id' => $download_id,
            'title' => isset($download['title']) ? (string) $download['title'] : '',
            'description' => wpautop(wp_kses_post($description)),
            'excerpt' => $excerpt,
            'download_url' => self::get_download_url($download_id),
            'download_count' => isset($download['download_count']) ? (int) $download['download_count'] : 0,
            'source' => isset($download['file_source']) ? (string) $download['file_source'] : 'media',
            'file_id' => isset($download['file_id']) ? (int) $download['file_id'] : 0,
            'direct_url' => isset($download['direct_url']) ? (string) $download['direct_url'] : '',
            'featured_image' => $featured_image_id > 0 ? (wp_get_attachment_image_url($featured_image_id, 'medium') ?: '') : '',
            'categories' => self::to_terms_payload(isset($download['categories']) ? (string) $download['categories'] : ''),
            'tags' => self::to_terms_payload(isset($download['tags']) ? (string) $download['tags'] : ''),
            'status' => isset($download['status']) ? (string) $download['status'] : 'publish',
            'created_at' => isset($download['created_at']) ? (string) $download['created_at'] : '',
            'updated_at' => isset($download['updated_at']) ? (string) $download['updated_at'] : '',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function to_terms_payload(string $csv): array {
        $terms = [];

        foreach (DownloadRepository::terms_from_csv($csv) as $slug) {
            $terms[] = [
                'id' => absint(crc32($slug)),
                'slug' => $slug,
                'name' => ucwords(str_replace(['-', '_'], ' ', $slug)),
            ];
        }

        return $terms;
    }

    private static function should_throttle(int $download_id): bool {
        $window = (int) apply_filters('gtdm_track_interval', self::THROTTLE_WINDOW);
        $cookie_name = 'gtdm_dl_' . $download_id;

        if (! headers_sent() && ! isset($_COOKIE[$cookie_name])) {
            setcookie(
                $cookie_name,
                '1',
                [
                    'expires' => time() + $window,
                    'path' => COOKIEPATH ?: '/',
                    'domain' => COOKIE_DOMAIN,
                    'secure' => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        }

        if (isset($_COOKIE[$cookie_name])) {
            return true;
        }

        $ip = self::get_request_ip();
        $transient_key = 'gtdm_tr_' . md5($download_id . '|' . $ip);

        if (get_transient($transient_key)) {
            return true;
        }

        set_transient($transient_key, 1, $window);

        return false;
    }

    private static function get_request_ip(): string {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($candidates as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }

            $raw = wp_unslash((string) $_SERVER[$key]);
            $ip = trim(explode(',', $raw)[0]);

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }
}
