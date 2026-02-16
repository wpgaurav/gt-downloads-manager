<?php

declare(strict_types=1);

namespace GTDownloadsManager\Blocks;

use GTDownloadsManager\Frontend\Assets;
use GTDownloadsManager\Frontend\Renderer;

class Manager {
    private const EDITOR_SCRIPT_HANDLE = 'gtdm-blocks-editor';

    public static function init(): void {
        add_action('init', [self::class, 'register']);
    }

    public static function register(): void {
        if (! function_exists('register_block_type')) {
            return;
        }

        self::register_editor_script();

        register_block_type('gtdm/downloads-query', self::downloads_query_args());
        register_block_type('gtdm/download-filters', self::download_filters_args());
        register_block_type('gtdm/download-card', self::download_card_args());
    }

    private static function register_editor_script(): void {
        $asset_file = GTDM_PATH . 'build/blocks/index.asset.php';

        if (! file_exists($asset_file)) {
            return;
        }

        $asset = require $asset_file;

        if (! is_array($asset) || ! isset($asset['dependencies'], $asset['version'])) {
            return;
        }

        wp_register_script(
            self::EDITOR_SCRIPT_HANDLE,
            GTDM_URL . 'build/blocks/index.js',
            $asset['dependencies'],
            (string) $asset['version'],
            true
        );

        wp_set_script_translations(self::EDITOR_SCRIPT_HANDLE, 'gt-downloads-manager');
    }

    /**
     * @return array<string, mixed>
     */
    private static function downloads_query_args(): array {
        return [
            'editor_script' => self::EDITOR_SCRIPT_HANDLE,
            'style' => Assets::block_style_handle(),
            'editor_style' => Assets::block_style_handle(),
            'render_callback' => [self::class, 'render_downloads_query'],
            'attributes' => [
                'category' => ['type' => 'string', 'default' => ''],
                'tag' => ['type' => 'string', 'default' => ''],
                'search' => ['type' => 'string', 'default' => ''],
                'sort' => ['type' => 'string', 'default' => 'newest'],
                'perPage' => ['type' => 'number', 'default' => 12],
                'page' => ['type' => 'number', 'default' => 1],
                'layout' => ['type' => 'string', 'default' => 'grid'],
                'filters' => ['type' => 'boolean', 'default' => true],
                'image' => ['type' => 'string', 'default' => 'medium'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function download_filters_args(): array {
        return [
            'editor_script' => self::EDITOR_SCRIPT_HANDLE,
            'style' => Assets::block_style_handle(),
            'editor_style' => Assets::block_style_handle(),
            'render_callback' => [self::class, 'render_download_filters'],
            'attributes' => [
                'category' => ['type' => 'string', 'default' => ''],
                'tag' => ['type' => 'string', 'default' => ''],
                'search' => ['type' => 'string', 'default' => ''],
                'sort' => ['type' => 'string', 'default' => 'newest'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function download_card_args(): array {
        return [
            'editor_script' => self::EDITOR_SCRIPT_HANDLE,
            'style' => Assets::block_style_handle(),
            'editor_style' => Assets::block_style_handle(),
            'render_callback' => [self::class, 'render_download_card'],
            'attributes' => [
                'id' => ['type' => 'number', 'default' => 0],
                'image' => ['type' => 'string', 'default' => 'medium'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function render_downloads_query(array $attributes): string {
        $attributes = wp_parse_args($attributes, [
            'category' => '',
            'tag' => '',
            'search' => '',
            'sort' => 'newest',
            'perPage' => 12,
            'page' => 1,
            'layout' => 'grid',
            'filters' => true,
            'image' => 'medium',
        ]);

        return Renderer::render_downloads([
            'category' => (string) $attributes['category'],
            'tag' => (string) $attributes['tag'],
            'search' => (string) $attributes['search'],
            'sort' => (string) $attributes['sort'],
            'per_page' => (int) $attributes['perPage'],
            'page' => (int) $attributes['page'],
            'layout' => (string) $attributes['layout'],
            'filters' => ! empty($attributes['filters']) ? 1 : 0,
            'image' => (string) $attributes['image'],
        ], ! empty($attributes['filters']), 'block');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function render_download_filters(array $attributes): string {
        Assets::enqueue_block_styles();

        $state = [
            'search' => (string) ($attributes['search'] ?? ''),
            'category' => (string) ($attributes['category'] ?? ''),
            'tag' => (string) ($attributes['tag'] ?? ''),
            'sort' => (string) ($attributes['sort'] ?? 'newest'),
            'page' => 1,
            'per_page' => 12,
            'layout' => 'grid',
            'filters' => 1,
        ];

        return Renderer::render_filters_form($state);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function render_download_card(array $attributes): string {
        $download_id = isset($attributes['id']) ? absint((string) $attributes['id']) : 0;
        $image = isset($attributes['image']) ? sanitize_key((string) $attributes['image']) : 'medium';

        if ($download_id <= 0) {
            $latest = \GTDownloadsManager\Domain\DownloadRepository::latest_published();

            if (! is_array($latest) || ! isset($latest['id'])) {
                return '';
            }

            $download_id = (int) $latest['id'];
        }

        return Renderer::render_single($download_id, $image, 'block');
    }
}
