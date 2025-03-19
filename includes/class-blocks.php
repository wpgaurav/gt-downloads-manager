<?php
namespace GTDownloadsManager;

class Blocks {
    private static $instance = null;
    private $downloads;
    private $preview_limit = 6; // Set a constant for preview limit

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->downloads = Downloads::instance();
        
        add_action('init', [$this, 'register_blocks']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Register custom blocks
     */
    public function register_blocks() {
        // Skip registration if Gutenberg is not available
        if (!function_exists('register_block_type')) {
            return;
        }

        // Register Single Download block
        register_block_type('gtdm/single-download', [
            'attributes' => [
                'id' => [
                    'type' => 'number',
                    'default' => 0
                ],
                'image' => [
                    'type' => 'string',
                    'default' => 'medium'
                ],
                'isPreview' => [
                    'type' => 'boolean',
                    'default' => false
                ]
            ],
            'render_callback' => [$this, 'render_single_download_block']
        ]);

        // Register Downloads List block
        register_block_type('gtdm/downloads-list', [
            'attributes' => [
                'category' => [
                    'type' => 'string',
                    'default' => ''
                ],
                'perPage' => [
                    'type' => 'number',
                    'default' => -1
                },
                'page' => [
                    'type' => 'number',
                    'default' => 1
                ],
                'type' => [
                    'type' => 'string',
                    'default' => 'grid'
                ],
                'image' => [
                    'type' => 'string',
                    'default' => 'medium'
                ],
                'isPreview' => [
                    'type' => 'boolean',
                    'default' => false
                }
            ],
            'render_callback' => [$this, 'render_downloads_list_block']
        ]);
    }

    /**
     * Register REST API routes for editor previews
     */
    public function register_rest_routes() {
        register_rest_route('gtdm/v1', '/preview-single/(?P<id>\d+)/(?P<image>[\w-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_single_download_preview'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        register_rest_route('gtdm/v1', '/preview-list', [
            'methods' => 'GET',
            'callback' => [$this, 'get_downloads_list_preview'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => [
                'category' => [
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'type' => [
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'image' => [
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
    }

    /**
     * REST API callback for single download preview
     */
    public function get_single_download_preview($request) {
        $id = $request['id'];
        $image = $request['image'];
        
        $download = $this->downloads->get_downloads(['id' => $id]);
        
        if (empty($download)) {
            return new \WP_Error('no_download', __('Download not found', 'gtdownloads-manager'), ['status' => 404]);
        }
        
        $html = $this->render_single_download_block([
            'id' => $id,
            'image' => $image,
            'isPreview' => true
        ]);
        
        return new \WP_REST_Response(['html' => $html]);
    }

    /**
     * REST API callback for downloads list preview
     */
    public function get_downloads_list_preview($request) {
        $category = $request->get_param('category') ?: '';
        $type = $request->get_param('type') ?: 'grid';
        $image = $request->get_param('image') ?: 'medium';
        
        $html = $this->render_downloads_list_block([
            'category' => $category,
            'perPage' => $this->preview_limit,
            'page' => 1,
            'type' => $type,
            'image' => $image,
            'isPreview' => true
        ]);
        
        return new \WP_REST_Response(['html' => $html]);
    }

    /**
     * Enqueue block editor assets
     */
    public function enqueue_editor_assets() {
        // Skip if Gutenberg is not available
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_enqueue_script(
            'gtdm-blocks-editor',
            DM_URL . 'assets/js/blocks.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-i18n', 'wp-data', 'wp-api-fetch'],
            DM_VERSION,
            true
        );

        // Pass available downloads to the editor
        $downloads = $this->downloads->get_downloads();
        $downloads_options = [];
        
        foreach ($downloads as $download) {
            $downloads_options[] = [
                'value' => $download->id,
                'label' => $download->title
            ];
        }

        // Get all unique categories
        global $wpdb;
        $categories = $wpdb->get_col("SELECT DISTINCT category FROM " . DM_TABLE . " WHERE category != '' ORDER BY category ASC");
        $category_options = [];

        foreach ($categories as $category) {
            $category_options[] = [
                'value' => $category,
                'label' => $category
            ];
        }

        // Get registered image sizes
        $image_sizes = get_intermediate_image_sizes();
        $image_size_options = [];

        foreach ($image_sizes as $size) {
            $image_size_options[] = [
                'value' => $size,
                'label' => ucfirst(str_replace('_', ' ', $size))
            ];
        }

        wp_localize_script('gtdm-blocks-editor', 'gtdmBlocks', [
            'downloads' => $downloads_options,
            'categories' => $category_options,
            'imageSizes' => $image_size_options,
            'previewLimit' => $this->preview_limit,
            'restUrl' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest')
        ]);

        // Add frontend styles for the editor
        wp_enqueue_style(
            'gtdm-blocks-editor-style',
            DM_URL . 'assets/css/blocks-editor.css',
            [],
            DM_VERSION
        );

        // Also enqueue frontend styles for previews
        wp_enqueue_style(
            'gtdm-frontend-style',
            DM_URL . 'assets/css/frontend.css',
            [],
            DM_VERSION
        );
        
        wp_enqueue_style(
            'gtdm-frontend-table-style',
            DM_URL . 'assets/css/frontend-table.css',
            [],
            DM_VERSION
        );
    }

    /**
     * Render single download block
     *
     * @param array $attributes Block attributes
     * @return string Rendered block HTML
     */
    public function render_single_download_block($attributes) {
        $attributes = wp_parse_args($attributes, [
            'id' => 0,
            'image' => 'medium',
            'isPreview' => false
        ]);
        
        $shortcode = \GTDownloadsManager\Shortcodes::instance();
        $output = $shortcode->single_download([
            'id' => $attributes['id'],
            'image' => $attributes['image']
        ]);
        
        if ($attributes['isPreview'] && !empty($output)) {
            // Add preview wrapper when in editor
            $output = '<div class="gtdm-editor-preview gtdm-editor-preview-single">' . $output . '</div>';
        }
        
        return $output;
    }

    /**
     * Render downloads list block
     *
     * @param array $attributes Block attributes
     * @return string Rendered block HTML
     */
    public function render_downloads_list_block($attributes) {
        $attributes = wp_parse_args($attributes, [
            'category' => '',
            'perPage' => -1,
            'page' => 1,
            'type' => 'grid',
            'image' => 'medium',
            'isPreview' => false
        ]);
        
        $per_page = $attributes['perPage'];
        
        // Limit items in preview mode
        if ($attributes['isPreview'] && ($per_page < 0 || $per_page > $this->preview_limit)) {
            $per_page = $this->preview_limit;
        }
        
        $shortcode = \GTDownloadsManager\Shortcodes::instance();
        $output = $shortcode->multiple_downloads([
            'category' => $attributes['category'],
            'per_page' => $per_page,
            'page' => $attributes['page'],
            'type' => $attributes['type'],
            'image' => $attributes['image']
        ]);
        
        if ($attributes['isPreview'] && !empty($output)) {
            // Add preview wrapper and notice when in editor
            $preview_notice = '';
            if ($attributes['perPage'] < 0 || $attributes['perPage'] > $this->preview_limit) {
                $preview_notice = '<div class="gtdm-preview-notice">' . 
                    sprintf(__('Preview limited to %d items. All items will be shown on the frontend.', 'gtdownloads-manager'), 
                    $this->preview_limit) . 
                    '</div>';
            }
            $output = '<div class="gtdm-editor-preview gtdm-editor-preview-list">' . 
                      $preview_notice . $output . '</div>';
        }
        
        return $output;
    }
}