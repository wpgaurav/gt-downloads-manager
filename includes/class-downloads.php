<?php
namespace GTDownloadsManager;

class Downloads {
    private static $instance = null;
    protected $wpdb;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function get_downloads(array $args = []) {
        $defaults = [
            'id' => null,
            'category' => null,
            'per_page' => -1,
            'page' => 1,
            'search' => null,
        ];
        
        $args = wp_parse_args($args, $defaults);
        $query = "SELECT * FROM " . DM_TABLE . " WHERE 1=1";
        $params = [];
    
        if ($args['id']) {
            $query .= " AND id = %d";
            $params[] = $args['id'];
        }
    
        if ($args['category']) {
            $query .= " AND category = %s";
            $params[] = $args['category'];
        }
        
        if ($args['search']) {
            $query .= " AND (title LIKE %s OR description LIKE %s)";
            $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
    
        $query .= " ORDER BY created_at DESC";
    
        if ($args['per_page'] > 0) {
            $offset = ($args['page'] - 1) * $args['per_page'];
            $query .= " LIMIT %d, %d";
            $params[] = $offset;
            $params[] = $args['per_page'];
        }
    
        if (!empty($params)) {
            $query = $this->wpdb->prepare($query, $params);
        }
    
        return $this->wpdb->get_results($query);
    }
    
    /**
     * Save a download (create or update)
     *
     * @param array $data Download data
     * @return bool|int False on failure, download ID on success
     */
    public function save_download(array $data) {
        $now = current_time('mysql');
        
        if (!empty($data['id'])) {
            // Update existing download
            $result = $this->wpdb->update(
                DM_TABLE,
                [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'featured_image_id' => $data['featured_image_id'],
                    'file_url' => $data['file_url'],
                    'direct_url' => $data['direct_url'],
                    'category' => $data['category'],
                    'updated_at' => $now
                ],
                ['id' => $data['id']],
                ['%s', '%s', '%d', '%d', '%s', '%s', '%s'],
                ['%d']
            );
            
            return $result !== false ? $data['id'] : false;
        } else {
            // Insert new download
            $result = $this->wpdb->insert(
                DM_TABLE,
                [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'featured_image_id' => $data['featured_image_id'],
                    'file_url' => $data['file_url'],
                    'direct_url' => $data['direct_url'],
                    'category' => $data['category'],
                    'created_at' => $now,
                    'updated_at' => $now
                ],
                ['%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s']
            );
            
            return $result ? $this->wpdb->insert_id : false;
        }
    }
    
    /**
     * Delete a download by ID
     *
     * @param int $id Download ID
     * @return bool True on success, false on failure
     */
    public function delete_download(int $id) {
        if ($id <= 0) return false;
        
        $result = $this->wpdb->delete(
            DM_TABLE,
            ['id' => $id],
            ['%d']
        );
        
        return $result !== false;
    }

    public function get_download_html(array $download) {
        $html = apply_filters('dm_before_download', '', $download);
        
        $image = $this->get_featured_image($download);
        $title = $this->get_title($download);
        $description = $this->get_description($download);
        $button = $this->get_download_button($download);
        $category = $this->get_category($download);
        $download_count = $this->get_download_count($download);
    
        $html .= sprintf(
            '<div class="dm-download">%s%s%s%s%s%s</div>',
            $image,
            $category,
            $title,
            $description,
            $button,
            $download_count
        );
    
        return apply_filters('dm_after_download', $html, $download);
    }
    
    protected function get_download_count(array $download) {
        if (!isset($download['download_count'])) return '';
        
        $count = intval($download['download_count']);
        if ($count <= 0) return '';
        
        $text = sprintf(
            _n('%s download', '%s downloads', $count, 'gtdownloads-manager'),
            number_format_i18n($count)
        );
        
        return sprintf('<div class="dm-download-count">%s</div>', esc_html($text));
    }

    protected function get_featured_image(array $download) {
        if (empty($download['featured_image_id'])) return '';
        
        // Get image size from the download array if available, otherwise default to 'medium'
        $image_size = isset($download['_image_size']) ? $download['_image_size'] : 'medium';
        
        $image = wp_get_attachment_image(
            $download['featured_image_id'],
            $image_size,
            false,
            ['class' => 'dm-featured-image']
        );
        
        if (!$image) return '';
        
        return $image;
    }

    protected function get_title(array $download) {
        $title = sprintf(
            '<h3 class="dm-title">%s</h3>',
            esc_html($download['title'])
        );
        return apply_filters('dm_title', $title, $download);
    }

    protected function get_description(array $download) {
        if (empty($download['description'])) return '';
        
        $description = sprintf(
            '<div class="dm-description">%s</div>',
            wpautop(esc_html($download['description']))
        );
        return apply_filters('dm_description', $description, $download);
    }

    protected function get_download_button(array $download) {
        // Replace this line:
        $url = $download['direct_url'] ?: wp_get_attachment_url($download['file_url']);
        // With:
        $url = $this->get_download_url($download);
        
        if (!$url) return '';
        
        $button = sprintf(
            '<a href="%s" class="dm-download-button" download>%s%s</a>',
            esc_url($url),
            $this->get_icon(),
            // Also fix the text domain here
            esc_html__('Download', 'gtdownloads-manager')
        );
        
        return apply_filters('dm_download_button', $button, $download, $url);
    }

    protected function get_category(array $download) {
        if (empty($download['category'])) return '';
        
        $category = sprintf(
            '<div class="dm-category">%s%s</div>',
            $this->get_category_icon(),
            esc_html($download['category'])
        );
        return apply_filters('dm_category', $category, $download);
    }

    protected function get_icon() {
        $icon = '<svg class="dm-icon" aria-hidden="true" viewBox="0 0 24 24" width="18" height="18"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>';
        return apply_filters('dm_download_icon', $icon);
    }

    protected function get_category_icon() {
        $icon = '<svg class="dm-category-icon" aria-hidden="true" viewBox="0 0 24 24" width="14" height="14"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>';
        return apply_filters('dm_category_icon', $icon);
    }
    // Add this to your Downloads class:

/**
 * Track download count
 * 
 * @param int $id Download ID
 * @return void
 */
public function track_download($id) {
    // Get current count
    $count = (int) $this->wpdb->get_var($this->wpdb->prepare(
        "SELECT download_count FROM " . DM_TABLE . " WHERE id = %d",
        $id
    ));
    
    // Increment count
    $this->wpdb->update(
        DM_TABLE,
        ['download_count' => $count + 1],
        ['id' => $id],
        ['%d'],
        ['%d']
    );
}

/**
 * Get download URL with tracking enabled
 * 
 * @param array $download Download data
 * @return string URL for tracking download
 */
public function get_download_url($download) {
    // For direct URLs, we'll use a redirect through our plugin to count
    if (!empty($download['direct_url'])) {
        return add_query_arg([
            'dm_download' => $download['id'],
            'nonce' => wp_create_nonce('download-' . $download['id'])
        ], site_url());
    }
    
    // For media library files, create a download URL through our plugin
    if (!empty($download['file_url'])) {
        return add_query_arg([
            'dm_download' => $download['id'],
            'nonce' => wp_create_nonce('download-' . $download['id'])
        ], site_url());
    }
    
    return '';
    }
}