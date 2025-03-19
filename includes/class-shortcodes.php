<?php
namespace GTDownloadsManager;

class Shortcodes {
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
        add_shortcode('gt_download', [$this, 'single_download']);
        add_shortcode('gt_downloads', [$this, 'multiple_downloads']);
        add_action('wp_enqueue_scripts', [$this, 'register_frontend_assets']);
    }
    
    public function register_frontend_assets() {
        wp_register_style(
            'gt-downloads-frontend', 
            DM_URL . 'assets/css/frontend.css',
            [],
            DM_VERSION
        );
        
        // Register table-specific CSS
        wp_register_style(
            'gt-downloads-frontend-table', 
            DM_URL . 'assets/css/frontend-table.css',
            [],
            DM_VERSION
        );
    }
    
    public function single_download($atts) {
        wp_enqueue_style('gt-downloads-frontend');
        $atts = shortcode_atts([
            'id' => 0, 
            'image' => 'medium' // Default image size
        ], $atts);
        
        $download = $this->downloads->get_downloads(['id' => $atts['id']]);
        
        if (empty($download)) return '';
        
        return $this->generate_output([$download[0]], 'grid', $atts['image']);
    }

    public function multiple_downloads($atts) {
        $atts = shortcode_atts([
            'category' => '',
            'per_page' => -1,
            'page' => 1,
            'type' => 'grid', // Default to grid, can be 'table' for table layout
            'image' => 'medium' // Default image size
        ], $atts);
        
        // Enqueue appropriate CSS based on layout type
        wp_enqueue_style('gt-downloads-frontend');
        if ($atts['type'] === 'table') {
            wp_enqueue_style('gt-downloads-frontend-table');
        }
        
        $downloads = $this->downloads->get_downloads([
            'category' => $atts['category'],
            'per_page' => $atts['per_page'],
            'page' => $atts['page']
        ]);
        
        return $this->generate_output($downloads, $atts['type'], $atts['image']);
    }

    public function generate_output(array $downloads, $type = 'grid', $image_size = 'medium') {
        if (empty($downloads)) return '';
        
        if ($type === 'table') {
            return $this->generate_table_output($downloads, $image_size);
        } else {
            return $this->generate_grid_output($downloads, $image_size);
        }
    }
    
    public function generate_grid_output(array $downloads, $image_size = 'medium') {
        $html = '<div class="dm-downloads-container">';
        foreach ($downloads as $download) {
            // Pass image size to Downloads class
            $download_array = (array)$download;
            // Add image_size to the download array for use in get_download_html
            $download_array['_image_size'] = $image_size;
            $html .= $this->downloads->get_download_html($download_array);
        }
        $html .= '</div>';
        
        return $html;
    }
    
    public function generate_table_output(array $downloads, $image_size = 'thumbnail') {
        $html = '<div class="dm-table-responsive">';
        $html .= '<table class="dm-downloads-table">';
        
        // Table header
        $html .= '<thead><tr>';
        $html .= '<th class="dm-col-image">' . esc_html__('Image', 'gt-downloads-manager') . '</th>';
        $html .= '<th class="dm-col-title">' . esc_html__('Title', 'gt-downloads-manager') . '</th>';
        $html .= '<th class="dm-col-category">' . esc_html__('Category', 'gt-downloads-manager') . '</th>';
        $html .= '<th class="dm-col-desc">' . esc_html__('Description', 'gt-downloads-manager') . '</th>';
        $html .= '<th class="dm-col-download">' . esc_html__('Download', 'gt-downloads-manager') . '</th>';
        $html .= '<th class="dm-col-count">' . esc_html__('Downloads', 'gt-downloads-manager') . '</th>';
        $html .= '</tr></thead>';
        
        // Table body
        $html .= '<tbody>';
        foreach ($downloads as $download) {
            $download = (array)$download;
            
            $html .= '<tr>';
            
            // Image column with custom size
            $html .= '<td class="dm-col-image" data-label="' . esc_attr__('Image', 'gt-downloads-manager') . '">';
            if (!empty($download['featured_image_id'])) {
                $html .= wp_get_attachment_image($download['featured_image_id'], $image_size);
            } else {
                $html .= '<span class="dm-no-image"></span>';
            }
            $html .= '</td>';
            
            // Title column
            $html .= '<td class="dm-col-title" data-label="' . esc_attr__('Title', 'gt-downloads-manager') . '">';
            $html .= '<h4>' . esc_html($download['title']) . '</h4>';
            $html .= '</td>';
            
            // Category column
            $html .= '<td class="dm-col-category" data-label="' . esc_attr__('Category', 'gt-downloads-manager') . '">';
            if (!empty($download['category'])) {
                $html .= '<span class="dm-category-label">' . esc_html($download['category']) . '</span>';
            }
            $html .= '</td>';
            
            // Description column
            $html .= '<td class="dm-col-desc" data-label="' . esc_attr__('Description', 'gt-downloads-manager') . '">';
            $html .= '<div class="dm-description">' . wp_kses_post($download['description']) . '</div>';
            $html .= '</td>';
            
            // Download button column
            $html .= '<td class="dm-col-download" data-label="' . esc_attr__('Download', 'gt-downloads-manager') . '">';
            $url = $this->downloads->get_download_url($download);
            if ($url) {
                $html .= sprintf(
                    '<a href="%s" class="dm-table-download-button">%s</a>',
                    esc_url($url),
                    esc_html__('Download', 'gt-downloads-manager')
                );
            }
            $html .= '</td>';
            
            // Download count column
            $html .= '<td class="dm-col-count" data-label="' . esc_attr__('Downloads', 'gt-downloads-manager') . '">';
            if (isset($download['download_count']) && $download['download_count'] > 0) {
                $html .= '<span class="dm-count-badge">' . number_format_i18n($download['download_count']) . '</span>';
            } else {
                $html .= '<span class="dm-count-badge dm-count-zero">0</span>';
            }
            $html .= '</td>';
            
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }
}