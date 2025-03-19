<?php
namespace GTDownloadsManager;

class Widget extends \WP_Widget {
    
    private $downloads;
    
    public function __construct() {
        parent::__construct(
            'gtdm_downloads_widget',
            __('GT Downloads', 'gt-downloads-manager'),
            ['description' => __('Display a list of downloadable resources', 'gt-downloads-manager')]
        );
        
        $this->downloads = Downloads::instance();
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $category = !empty($instance['category']) ? $instance['category'] : '';
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 5;
        
        $downloads = $this->downloads->get_downloads([
            'category' => $category,
            'per_page' => $limit
        ]);
        
        if (!empty($downloads)) {
            echo '<ul class="dm-widget-downloads">';
            foreach ($downloads as $download) {
                $url = $this->downloads->get_download_url((array)$download);
                echo '<li>';
                echo '<a href="' . esc_url($url) . '">' . esc_html($download->title) . '</a>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . esc_html__('No downloads available.', 'gt-downloads-manager') . '</p>';
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Downloads', 'gt-downloads-manager');
        $category = !empty($instance['category']) ? $instance['category'] : '';
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 5;
        
        // Get all unique categories for dropdown
        global $wpdb;
        $categories = [];
        
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", DM_TABLE)) === DM_TABLE) {
            $categories = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT category FROM %s WHERE category != '' ORDER BY category ASC", DM_TABLE));
        }
        
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'gt-downloads-manager'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('category')); ?>"><?php esc_html_e('Category:', 'gt-downloads-manager'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('category')); ?>" name="<?php echo esc_attr($this->get_field_name('category')); ?>">
                <option value="" <?php selected(empty($category)); ?>><?php esc_html_e('All Categories', 'gt-downloads-manager'); ?></option>
                <?php foreach ($categories as $cat) : ?>
                    <option value="<?php echo esc_attr($cat); ?>" <?php selected($category, $cat); ?>>
                        <?php echo esc_html($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>"><?php esc_html_e('Number to show:', 'gt-downloads-manager'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('limit')); ?>" name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="number" min="1" max="20" value="<?php echo esc_attr($limit); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['category'] = (!empty($new_instance['category'])) ? sanitize_text_field($new_instance['category']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 5;
        return $instance;
    }
}