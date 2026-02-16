<?php

declare(strict_types=1);

namespace GTDownloadsManager\Frontend;

class Shortcodes {
    public static function init(): void {
        add_shortcode('gtdm_download', [self::class, 'render_download']);
        add_shortcode('gtdm_downloads', [self::class, 'render_downloads']);
    }

    /**
     * @param array<string, mixed> $atts
     */
    public static function render_download(array $atts): string {
        $atts = shortcode_atts([
            'id' => 0,
            'image' => 'medium',
        ], $atts, 'gtdm_download');

        $download_id = absint($atts['id']);
        $image = is_string($atts['image']) ? sanitize_key($atts['image']) : 'medium';

        if ($download_id <= 0) {
            return '';
        }

        return Renderer::render_single($download_id, $image);
    }

    /**
     * @param array<string, mixed> $atts
     */
    public static function render_downloads(array $atts): string {
        $atts = shortcode_atts([
            'category' => '',
            'tag' => '',
            'search' => '',
            'sort' => 'newest',
            'per_page' => 12,
            'page' => 1,
            'layout' => 'grid',
            'filters' => 1,
            'image' => 'medium',
        ], $atts, 'gtdm_downloads');

        $input = [
            'category' => is_string($atts['category']) ? $atts['category'] : '',
            'tag' => is_string($atts['tag']) ? $atts['tag'] : '',
            'search' => is_string($atts['search']) ? $atts['search'] : '',
            'sort' => is_string($atts['sort']) ? $atts['sort'] : 'newest',
            'per_page' => absint((string) $atts['per_page']),
            'page' => absint((string) $atts['page']),
            'layout' => is_string($atts['layout']) ? $atts['layout'] : 'grid',
            'filters' => absint((string) $atts['filters']),
            'image' => is_string($atts['image']) ? sanitize_key($atts['image']) : 'medium',
        ];

        $merge_request = (int) $input['filters'] === 1;

        return Renderer::render_downloads($input, $merge_request);
    }
}
