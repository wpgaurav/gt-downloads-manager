<?php

declare(strict_types=1);

namespace GTDownloadsManager\Rest;

use GTDownloadsManager\Domain\DownloadRepository;
use GTDownloadsManager\Domain\DownloadService;
use GTDownloadsManager\Domain\QueryService;
use GTDownloadsManager\Frontend\Renderer;

class Api {
    private const NAMESPACE = 'gtdm/v2';

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void {
        register_rest_route(
            self::NAMESPACE,
            '/downloads',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [self::class, 'get_downloads'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'search' => [
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'category' => [
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_title',
                        ],
                        'tag' => [
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_title',
                        ],
                        'sort' => [
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => [QueryService::class, 'sanitize_sort'],
                        ],
                        'page' => [
                            'type' => 'integer',
                            'required' => false,
                            'sanitize_callback' => 'absint',
                        ],
                        'per_page' => [
                            'type' => 'integer',
                            'required' => false,
                            'sanitize_callback' => 'absint',
                        ],
                        'layout' => [
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_key',
                        ],
                        'context_url' => [
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'esc_url_raw',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/downloads/(?P<id>\\d+)',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [self::class, 'get_download'],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/downloads/search',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [self::class, 'search_downloads'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'search' => [
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'per_page' => [
                            'type' => 'integer',
                            'required' => false,
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/terms/categories',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [self::class, 'get_categories'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'search' => [
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'per_page' => [
                            'type' => 'integer',
                            'required' => false,
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/terms/tags',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [self::class, 'get_tags'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'search' => [
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'per_page' => [
                            'type' => 'integer',
                            'required' => false,
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/downloads/(?P<id>\\d+)/track',
            [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [self::class, 'track_download'],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
    }

    public static function get_downloads(\WP_REST_Request $request): \WP_REST_Response {
        $built = QueryService::build_query_args([
            'search' => (string) $request->get_param('search'),
            'category' => (string) $request->get_param('category'),
            'tag' => (string) $request->get_param('tag'),
            'sort' => (string) $request->get_param('sort'),
            'page' => (int) $request->get_param('page'),
            'per_page' => (int) $request->get_param('per_page'),
            'layout' => (string) $request->get_param('layout'),
            'filters' => 0,
        ], false);

        $context_url = (string) $request->get_param('context_url');

        if ($context_url !== '') {
            $built['state']['base_url'] = esc_url_raw($context_url);
        }

        $result = DownloadRepository::query($built['query_args']);

        $items = array_map(
            static fn(array $download): array => DownloadService::get_download_payload($download),
            $result['items']
        );

        $response = [
            'items' => $items,
            'html' => Renderer::render_results_markup($result, $built['state']),
            'page' => (int) $result['page'],
            'per_page' => (int) $result['per_page'],
            'total' => (int) $result['total'],
            'total_pages' => (int) $result['total_pages'],
            'state' => $built['state'],
        ];

        return rest_ensure_response($response);
    }

    public static function get_download(\WP_REST_Request $request) {
        $download_id = absint((string) $request['id']);
        $download = DownloadRepository::find($download_id);

        if (! is_array($download) || ($download['status'] ?? '') !== 'publish') {
            return new \WP_Error(
                'gtdm_not_found',
                __('Download not found.', 'gt-downloads-manager'),
                ['status' => 404]
            );
        }

        return rest_ensure_response(DownloadService::get_download_payload($download));
    }

    public static function search_downloads(\WP_REST_Request $request): \WP_REST_Response {
        $search = (string) $request->get_param('search');
        $per_page = (int) $request->get_param('per_page');
        $per_page = $per_page > 0 ? $per_page : 12;

        return rest_ensure_response(DownloadRepository::search_by_title($search, $per_page));
    }

    public static function get_categories(\WP_REST_Request $request): \WP_REST_Response {
        $search = (string) $request->get_param('search');
        $per_page = (int) $request->get_param('per_page');
        $per_page = $per_page > 0 ? $per_page : 10;

        return rest_ensure_response(DownloadRepository::suggest_terms('categories', $search, $per_page));
    }

    public static function get_tags(\WP_REST_Request $request): \WP_REST_Response {
        $search = (string) $request->get_param('search');
        $per_page = (int) $request->get_param('per_page');
        $per_page = $per_page > 0 ? $per_page : 10;

        return rest_ensure_response(DownloadRepository::suggest_terms('tags', $search, $per_page));
    }

    public static function track_download(\WP_REST_Request $request) {
        $download_id = absint((string) $request['id']);
        $download = DownloadRepository::find($download_id);

        if (! is_array($download) || ($download['status'] ?? '') !== 'publish') {
            return new \WP_Error(
                'gtdm_not_found',
                __('Download not found.', 'gt-downloads-manager'),
                ['status' => 404]
            );
        }

        $tracked = DownloadService::track_download($download_id);
        $count = DownloadRepository::get_download_count($download_id);

        return rest_ensure_response([
            'tracked' => $tracked,
            'throttled' => ! $tracked,
            'download_count' => $count,
        ]);
    }
}
