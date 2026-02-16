<?php

declare(strict_types=1);

namespace GTDownloadsManager\Frontend;

class Assets {
    private static bool $registered = false;
    private const STYLE_FRONTEND = 'gtdm-frontend';
    private const STYLE_BLOCK = 'gtdm-block';
    private const SCRIPT_FRONTEND = 'gtdm-frontend';

    public static function init(): void {
        add_action('init', [self::class, 'register'], 5);
        add_action('wp_enqueue_scripts', [self::class, 'register']);
    }

    public static function register(): void {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        wp_register_style(
            self::STYLE_FRONTEND,
            GTDM_URL . 'assets/css/frontend.css',
            [],
            GTDM_VERSION
        );

        wp_register_style(
            self::STYLE_BLOCK,
            GTDM_URL . 'assets/css/blocks-frontend.css',
            [],
            GTDM_VERSION
        );

        wp_register_script(
            self::SCRIPT_FRONTEND,
            GTDM_URL . 'assets/js/frontend.js',
            [],
            GTDM_VERSION,
            true
        );

        wp_localize_script(self::SCRIPT_FRONTEND, 'gtdmFrontend', [
            'restBase' => esc_url_raw(rest_url('gtdm/v2/downloads')),
        ]);
    }

    public static function enqueue_frontend(): void {
        if (! self::$registered) {
            self::register();
        }

        wp_enqueue_style(self::STYLE_FRONTEND);
        wp_enqueue_script(self::SCRIPT_FRONTEND);
    }

    public static function enqueue_block_styles(): void {
        if (! self::$registered) {
            self::register();
        }

        wp_enqueue_style(self::STYLE_BLOCK);
    }

    public static function enqueue_script(): void {
        if (! self::$registered) {
            self::register();
        }

        wp_enqueue_script(self::SCRIPT_FRONTEND);
    }

    public static function block_style_handle(): string {
        return self::STYLE_BLOCK;
    }
}
