<?php
namespace GTDownloadsManager;

class Admin {
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
        
        // Register admin menu
        add_action('admin_menu', [$this, 'register_admin_menu']);
        
        // Register admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_dm_save_download', [$this, 'ajax_save_download']);
        add_action('wp_ajax_dm_delete_download', [$this, 'ajax_delete_download']);
    }

    public function register_admin_menu() {
        add_menu_page(
            __('Downloads Manager', 'gtdownloads-manager'),
            __('Downloads', 'gtdownloads-manager'),
            'manage_options',
            'gt-downloads-manager',
            [$this, 'render_admin_page'],
            'dashicons-download',
            30
        );
    }

    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_gt-downloads-manager' !== $hook) {
            return;
        }

        wp_enqueue_media(); // For featured image selection
        
        wp_enqueue_style(
            'gt-downloads-admin-css',
            DM_URL . 'assets/css/admin.css',
            [],
            DM_VERSION
        );

        wp_enqueue_script(
            'gt-downloads-admin-js',
            DM_URL . 'assets/js/admin.js',
            ['jquery', 'wp-util'],
            DM_VERSION,
            true
        );

        wp_localize_script('gt-downloads-admin-js', 'dmAdmin', [
    'nonce' => wp_create_nonce('dm-admin-nonce'),
    'ajaxurl' => admin_url('admin-ajax.php'),
    'i18n' => [
        'confirmDelete' => __('Are you sure you want to delete this download? This action cannot be undone.', 'gtdownloads-manager'),
        'errorSaving' => __('Error saving download.', 'gtdownloads-manager'),
        'errorDeleting' => __('Error deleting download.', 'gtdownloads-manager'),
        'selectImage' => __('Select Image', 'gtdownloads-manager'),
        'useImage' => __('Use this image', 'gtdownloads-manager'),
        'selectFile' => __('Select File', 'gtdownloads-manager'),
        'useFile' => __('Use this file', 'gtdownloads-manager'),
        'noImage' => __('No image selected', 'gtdownloads-manager'),
        'noFile' => __('No file selected', 'gtdownloads-manager'),
        'noDownloads' => __('No downloads found.', 'gtdownloads-manager'),
        'successSaving' => __('Download saved successfully.', 'gtdownloads-manager'),
        'successDeleting' => __('Download deleted successfully.', 'gtdownloads-manager')
    ]
]);
    }

    public function render_admin_page() {
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        switch ($action) {
            case 'new':
                $this->render_form();
                break;

            case 'edit':
                $this->render_form($id);
                break;

            default:
                $this->render_downloads_list();
                break;
        }
    }

    private function render_downloads_list() {
        $per_page = 10; // Number of downloads per page
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        
        // Get category filter if present
    $category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
    
    // Get search term if present
    $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    // Get total count for pagination
    $total_downloads = count($this->downloads->get_downloads([
        'category' => $category_filter,
        'search' => $search_term
    ]));
    $total_pages = ceil($total_downloads / $per_page);
        
        // Get paginated results
        $downloads = $this->downloads->get_downloads([
            'category' => $category_filter,
            'search' => $search_term,
            'per_page' => $per_page,
            'page' => $current_page
        ]);
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Downloads Manager', 'gtdownloads-manager'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=gt-downloads-manager&action=new')); ?>" class="page-title-action"><?php _e('Add New', 'gtdownloads-manager'); ?></a>
            
            <hr class="wp-header-end">
            
            <div class="notice notice-info notice-alt">
                <p><?php _e('Use shortcodes to display downloads on your site:', 'gtdownloads-manager'); ?></p>
                <p><code>[gt_downloads]</code> - <?php _e('Display all downloads. Defaults to grid. Add type="table" to show table layout.', 'gtdownloads-manager'); ?></p>
                <p><code>[gt_downloads category="category-name"]</code> - <?php _e('Display downloads from a specific category', 'gtdownloads-manager'); ?></p>
                <p><code>[gt_download id="123"]</code> - <?php _e('Display a specific download', 'gtdownloads-manager'); ?></p>
                <p><code>[gt_downloads type="table" image="thumbnail"]</code> - <?php _e('Display all downloads with thumbnail size featured image in table layout.', 'gtdownloads-manager'); ?></p>
                <p><a href="https://gauravtiwari.org/snippet/gt-downloads-manager-plugin/" target="_blank"> <?php _e('See detailed documentation', 'gtdownloads-manager'); ?></a></p>
            </div>
            <?php
            // Get all unique categories
            global $wpdb;
            $all_categories = $wpdb->get_col("SELECT DISTINCT category FROM " . DM_TABLE . " WHERE category != '' ORDER BY category ASC");
            $selected_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
        

if (!empty($all_categories)) : ?>
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="gt-downloads-manager">
                <select name="category">
                    <option value=""><?php _e('All Categories', 'gtdownloads-manager'); ?></option>
                    <?php foreach ($all_categories as $cat) : ?>
                        <option value="<?php echo esc_attr($cat); ?>" <?php selected($selected_category, $cat); ?>>
                            <?php echo esc_html($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" class="button" value="<?php _e('Filter', 'gtdownloads-manager'); ?>">
            </form>
        </div>
        <div class="tablenav-pages search-box">
        <form method="get">
            <input type="hidden" name="page" value="gt-downloads-manager">
            <?php if ($selected_category) : ?>
                <input type="hidden" name="category" value="<?php echo esc_attr($selected_category); ?>">
            <?php endif; ?>
            <label class="screen-reader-text" for="dm-search-input"><?php _e('Search Downloads:', 'gtdownloads-manager'); ?></label>
            <input type="search" id="dm-search-input" name="s" value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
            <input type="submit" id="search-submit" class="button" value="<?php _e('Search Downloads', 'gtdownloads-manager'); ?>">
        </form>
    </div>
        <br class="clear">
    </div>
<?php endif; ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'gtdownloads-manager'); ?></th>
                        <th><?php _e('Title', 'gtdownloads-manager'); ?></th>
                        <th><?php _e('Category', 'gtdownloads-manager'); ?></th>
                        <th><?php _e('Date', 'gtdownloads-manager'); ?></th>
                        <th><?php _e('Actions', 'gtdownloads-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($downloads)) : ?>
                        <tr>
                            <td colspan="5"><?php _e('No downloads found.', 'gtdownloads-manager'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($downloads as $download) : ?>
                            <tr>
                                <td><?php echo esc_html($download->id); ?></td>
                                <td>
                                    <strong><?php echo esc_html($download->title); ?></strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=gt-downloads-manager&action=edit&id=' . $download->id)); ?>"><?php _e('Edit', 'gtdownloads-manager'); ?></a> |
                                        </span>
                                        <span class="trash">
                                            <a href="#" class="dm-delete" data-id="<?php echo esc_attr($download->id); ?>"><?php _e('Delete', 'gtdownloads-manager'); ?></a>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo esc_html($download->category); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($download->created_at))); ?></td>
                                <td>
                                    <code>[gt_download id="<?php echo esc_attr($download->id); ?>"]</code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s item', '%s items', $total_downloads, 'gtdownloads-manager'), number_format_i18n($total_downloads)); ?>
                </span>
                <span class="pagination-links">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ]);
                    ?>
                </span>
            </div>
            <br class="clear">
        </div>
        <?php endif; ?>
        </div>
        <?php
    }

    private function render_form($id = 0) {
        $download = null;
        
        if ($id > 0) {
            $results = $this->downloads->get_downloads(['id' => $id]);
            $download = !empty($results) ? (array)$results[0] : null;
            
            if (!$download) {
                wp_die(__('Download not found.', 'gtdownloads-manager'));
            }
        }

        $is_new = empty($download);
        ?>
        <div class="wrap">
            <h1><?php echo $is_new ? __('Add New Download', 'gtdownloads-manager') : __('Edit Download', 'gtdownloads-manager'); ?></h1>
            
            <form id="dm-download-form" class="dm-form">
                <?php wp_nonce_field('dm-save-download', 'dm-nonce'); ?>
                
                <?php if (!$is_new) : ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($download['id']); ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="title"><?php _e('Title', 'gtdownloads-manager'); ?></label></th>
                            <td>
                                <input name="title" type="text" id="title" class="regular-text" value="<?php echo $is_new ? '' : esc_attr($download['title']); ?>" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="category"><?php _e('Category', 'gtdownloads-manager'); ?></label></th>
                            <td>
                                <input name="category" type="text" id="category" class="regular-text" value="<?php echo $is_new ? '' : esc_attr($download['category']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="description"><?php _e('Description', 'gtdownloads-manager'); ?></label></th>
                            <td>
                                <textarea name="description" id="description" class="large-text" rows="5"><?php echo $is_new ? '' : esc_textarea($download['description']); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php _e('Featured Image', 'gtdownloads-manager'); ?></label></th>
                            <td>
                                <div class="dm-featured-image-container">
                                    <div class="dm-featured-image-preview">
                                        <?php 
                                        if (!$is_new && !empty($download['featured_image_id'])) {
                                            echo wp_get_attachment_image($download['featured_image_id'], 'medium');
                                        } else {
                                            echo '<div class="dm-no-image">' . __('No image selected', 'gtdownloads-manager') . '</div>';
                                        }
                                        ?>
                                    </div>
                                    <input type="hidden" name="featured_image_id" id="featured_image_id" value="<?php echo $is_new ? '' : esc_attr($download['featured_image_id']); ?>">
                                    <button type="button" class="button dm-select-image"><?php _e('Select Image', 'gtdownloads-manager'); ?></button>
                                    <button type="button" class="button dm-remove-image"><?php _e('Remove Image', 'gtdownloads-manager'); ?></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php _e('Download File', 'gtdownloads-manager'); ?></label></th>
                            <td>
                                <div class="dm-file-selection">
                                    <p>
                                        <label>
                                            <input type="radio" name="file_source" value="media" <?php echo $is_new || (!empty($download['file_url']) && empty($download['direct_url'])) ? 'checked' : ''; ?>>
                                            <?php _e('Upload file to Media Library', 'gtdownloads-manager'); ?>
                                        </label>
                                    </p>
                                    
                                    <div class="dm-upload-container">
                                        <div class="dm-file-preview">
                                            <?php if (!$is_new && !empty($download['file_url'])) : ?>
                                                <?php $file_url = wp_get_attachment_url($download['file_url']); ?>
                                                <?php if ($file_url) : ?>
                                                    <span class="dm-filename"><?php echo esc_html(basename($file_url)); ?></span>
                                                <?php endif; ?>
                                            <?php else : ?>
                                                <span class="dm-no-file"><?php _e('No file selected', 'gtdownloads-manager'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <input type="hidden" name="file_url" id="file_url" value="<?php echo $is_new ? '' : esc_attr($download['file_url']); ?>">
                                        <button type="button" class="button dm-select-file"><?php _e('Select File', 'gtdownloads-manager'); ?></button>
                                        <button type="button" class="button dm-remove-file"><?php _e('Remove File', 'gtdownloads-manager'); ?></button>
                                    </div>
                                    
                                    <p>
                                        <label>
                                            <input type="radio" name="file_source" value="direct" <?php echo !$is_new && !empty($download['direct_url']) ? 'checked' : ''; ?>>
                                            <?php _e('Direct URL', 'gtdownloads-manager'); ?>
                                        </label>
                                    </p>
                                    
                                    <div class="dm-direct-url-container">
                                        <input name="direct_url" type="url" id="direct_url" class="regular-text" value="<?php echo $is_new ? '' : esc_attr($download['direct_url']); ?>" placeholder="https://...">
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="dm-form-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=gt-downloads-manager')); ?>" class="button"><?php _e('Cancel', 'gtdownloads-manager'); ?></a>
                    <button type="submit" class="button button-primary"><?php _e('Save Download', 'gtdownloads-manager'); ?></button>
                </div>
                
                <div id="dm-form-feedback" class="notice notice-success hidden">
                    <p></p>
                </div>
            </form>
        </div>
        <?php
    }

    public function ajax_save_download() {
        check_ajax_referer('dm-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'gtdownloads-manager')]);
        }

        $data = [
            'id' => isset($_POST['id']) ? intval($_POST['id']) : 0,
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'featured_image_id' => intval($_POST['featured_image_id'] ?? 0),
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'direct_url' => '',
            'file_url' => 0
        ];

        if (empty($data['title'])) {
            wp_send_json_error(['message' => __('Title is required.', 'gtdownloads-manager')]);
        }

        $file_source = sanitize_key($_POST['file_source'] ?? 'media');
        
        if ($file_source === 'media') {
            $data['file_url'] = intval($_POST['file_url'] ?? 0);
            $data['direct_url'] = '';
        } else {
            $data['direct_url'] = esc_url_raw($_POST['direct_url'] ?? '');
            $data['file_url'] = 0;
        }

        if (empty($data['file_url']) && empty($data['direct_url'])) {
            wp_send_json_error(['message' => __('Please specify a download file or URL.', 'gtdownloads-manager')]);
        }

        // Use the Downloads class to save the data
        $result = $this->downloads->save_download($data);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Download saved successfully!', 'gtdownloads-manager'),
                'redirect' => admin_url('admin.php?page=gt-downloads-manager')
            ]);
        } else {
            wp_send_json_error(['message' => __('Error saving download.', 'gtdownloads-manager')]);
        }
    }

    public function ajax_delete_download() {
        check_ajax_referer('dm-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'gtdownloads-manager')]);
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            wp_send_json_error(['message' => __('Invalid download ID.', 'gtdownloads-manager')]);
        }

        // Use the Downloads class to delete the download
        $result = $this->downloads->delete_download($id);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Download deleted successfully!', 'gtdownloads-manager')
            ]);
        } else {
            wp_send_json_error(['message' => __('Error deleting download.', 'gtdownloads-manager')]);
        }
    }
}