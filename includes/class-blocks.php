<?php
// filepath: /Users/gauravtiwari/Development/gt-downloads-manager/includes/class-blocks.php
<?php
namespace GTDownloadsManager;

class Blocks {
    private static $instance = null;
    private $downloads;

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
                ],
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
                ]
            ],
            'render_callback' => [$this, 'render_downloads_list_block']
        ]);
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
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-i18n', 'wp-data'],
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
            'imageSizes' => $image_size_options
        ]);

        // Add editor styles
        wp_enqueue_style(
            'gtdm-blocks-editor-style',
            DM_URL . 'assets/css/blocks-editor.css',
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
            'image' => 'medium'
        ]);
        
        $shortcode = \GTDownloadsManager\Shortcodes::instance();
        return $shortcode->single_download([
            'id' => $attributes['id'],
            'image' => $attributes['image']
        ]);
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
            'image' => 'medium'
        ]);
        
        $shortcode = \GTDownloadsManager\Shortcodes::instance();
        return $shortcode->multiple_downloads([
            'category' => $attributes['category'],
            'per_page' => $attributes['perPage'],
            'page' => $attributes['page'],
            'type' => $attributes['type'],
            'image' => $attributes['image']
        ]);
    }
}