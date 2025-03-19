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
    }
    public function single_download($atts) {
        wp_enqueue_style('gt-downloads-frontend');
        $atts = shortcode_atts(['id' => 0], $atts);
        $download = $this->downloads->get_downloads(['id' => $atts['id']]);
        
        if (empty($download)) return '';
        
        return $this->generate_output([$download[0]]);
    }

    public function multiple_downloads($atts) {
        wp_enqueue_style('gt-downloads-frontend');
        $atts = shortcode_atts([
            'category' => '',
            'per_page' => -1,
            'page' => 1
        ], $atts);
        
        $downloads = $this->downloads->get_downloads([
            'category' => $atts['category'],
            'per_page' => $atts['per_page'],
            'page' => $atts['page']
        ]);
        
        return $this->generate_output($downloads);
    }

    private function generate_output(array $downloads) {
        if (empty($downloads)) return '';
        
        $html = '<div class="dm-downloads-container">';
        foreach ($downloads as $download) {
            $html .= $this->downloads->get_download_html((array)$download);
        }
        $html .= '</div>';
        
        return $html;
    }
}