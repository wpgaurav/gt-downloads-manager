<?php

declare(strict_types=1);

namespace GTDownloadsManager\Admin;

use GTDownloadsManager\Domain\DownloadRepository;

class Admin {
    private const MENU_SLUG = 'gtdm-downloads';
    private const PAGE_DOCS = 'gtdm-docs';
    private const PAGE_CATEGORIES = 'gtdm-download-categories';
    private const PAGE_TAGS = 'gtdm-download-tags';

    public static function init(): void {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('admin_post_gtdm_save_download', [self::class, 'handle_save_download']);
        add_action('admin_post_gtdm_delete_download', [self::class, 'handle_delete_download']);
        add_action('admin_post_gtdm_save_term', [self::class, 'handle_save_term']);
        add_action('admin_post_gtdm_delete_term', [self::class, 'handle_delete_term']);
        add_action('admin_notices', [self::class, 'render_notices']);
    }

    public static function register_menu(): void {
        add_menu_page(
            __('GT Downloads Manager', 'gt-downloads-manager'),
            __('Downloads', 'gt-downloads-manager'),
            'edit_others_posts',
            self::MENU_SLUG,
            [self::class, 'render_page'],
            'dashicons-download',
            26
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('All Downloads', 'gt-downloads-manager'),
            __('All Downloads', 'gt-downloads-manager'),
            'edit_others_posts',
            self::MENU_SLUG,
            [self::class, 'render_page']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Download Categories', 'gt-downloads-manager'),
            __('Categories', 'gt-downloads-manager'),
            'edit_others_posts',
            self::PAGE_CATEGORIES,
            [self::class, 'render_categories_page']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Download Tags', 'gt-downloads-manager'),
            __('Tags', 'gt-downloads-manager'),
            'edit_others_posts',
            self::PAGE_TAGS,
            [self::class, 'render_tags_page']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Docs', 'gt-downloads-manager'),
            __('Docs', 'gt-downloads-manager'),
            'edit_others_posts',
            self::PAGE_DOCS,
            [self::class, 'render_docs_page']
        );
    }

    public static function enqueue_assets(): void {
        if (! self::is_plugin_admin_page()) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'gtdm-admin',
            GTDM_URL . 'assets/css/admin.css',
            [],
            GTDM_VERSION
        );

        wp_enqueue_script(
            'gtdm-admin',
            GTDM_URL . 'assets/js/admin.js',
            ['jquery'],
            GTDM_VERSION,
            true
        );

        wp_localize_script('gtdm-admin', 'gtdmAdmin', [
            'chooseFile' => __('Choose file', 'gt-downloads-manager'),
            'chooseImage' => __('Choose image', 'gt-downloads-manager'),
            'useFile' => __('Use this file', 'gt-downloads-manager'),
            'useImage' => __('Use this image', 'gt-downloads-manager'),
            'noFile' => __('No file selected', 'gt-downloads-manager'),
            'noImage' => __('No image selected', 'gt-downloads-manager'),
            'confirmDeleteTerm' => __('Delete this term from all downloads?', 'gt-downloads-manager'),
        ]);
    }

    public static function render_page(): void {
        if (! current_user_can('edit_others_posts')) {
            wp_die(esc_html__('You are not allowed to access this page.', 'gt-downloads-manager'));
        }

        $action = isset($_GET['action']) ? sanitize_key((string) wp_unslash($_GET['action'])) : 'list';
        $download_id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;

        if ($action === 'new' || ($action === 'edit' && $download_id > 0)) {
            self::render_form($download_id);
            return;
        }

        self::render_list();
    }

    private static function render_list(): void {
        $page = isset($_GET['paged']) ? max(1, absint(wp_unslash($_GET['paged']))) : 1;
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $category = isset($_GET['category']) ? sanitize_title(wp_unslash($_GET['category'])) : '';
        $tag = isset($_GET['tag']) ? sanitize_title(wp_unslash($_GET['tag'])) : '';

        $result = DownloadRepository::query([
            'search' => $search,
            'category' => $category,
            'tag' => $tag,
            'page' => $page,
            'per_page' => 20,
            'status' => '',
        ]);

        $categories = DownloadRepository::aggregate_terms('categories', '');
        $tags = DownloadRepository::aggregate_terms('tags', '');

        ?>
        <div class="wrap gtdm-admin-page gtdm-admin-list-page">
            <div class="gtdm-admin-header">
                <h1 class="wp-heading-inline"><?php esc_html_e('Downloads', 'gt-downloads-manager'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=' . self::MENU_SLUG . '&action=new')); ?>" class="page-title-action">
                    <?php esc_html_e('Add New', 'gt-downloads-manager'); ?>
                </a>
            </div>
            <hr class="wp-header-end" />

            <form method="get" class="gtdm-filter-shell">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::MENU_SLUG); ?>" />

                <label>
                    <span class="screen-reader-text"><?php esc_html_e('Search downloads', 'gt-downloads-manager'); ?></span>
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search downloads', 'gt-downloads-manager'); ?>" />
                </label>

                <select name="category">
                    <option value=""><?php esc_html_e('All categories', 'gt-downloads-manager'); ?></option>
                    <?php foreach ($categories as $item) : ?>
                        <option value="<?php echo esc_attr((string) $item['slug']); ?>" <?php selected($category, (string) $item['slug']); ?>>
                            <?php echo esc_html((string) $item['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="tag">
                    <option value=""><?php esc_html_e('All tags', 'gt-downloads-manager'); ?></option>
                    <?php foreach ($tags as $item) : ?>
                        <option value="<?php echo esc_attr((string) $item['slug']); ?>" <?php selected($tag, (string) $item['slug']); ?>>
                            <?php echo esc_html((string) $item['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="button button-secondary"><?php esc_html_e('Filter', 'gt-downloads-manager'); ?></button>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'gt-downloads-manager'); ?></th>
                        <th><?php esc_html_e('Title', 'gt-downloads-manager'); ?></th>
                        <th><?php esc_html_e('Source', 'gt-downloads-manager'); ?></th>
                        <th><?php esc_html_e('Categories', 'gt-downloads-manager'); ?></th>
                        <th><?php esc_html_e('Tags', 'gt-downloads-manager'); ?></th>
                        <th><?php esc_html_e('Downloads', 'gt-downloads-manager'); ?></th>
                        <th><?php esc_html_e('Status', 'gt-downloads-manager'); ?></th>
                        <th><?php esc_html_e('Shortcode', 'gt-downloads-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (($result['items'] ?? []) === []) : ?>
                        <tr>
                            <td colspan="8"><?php esc_html_e('No downloads found.', 'gt-downloads-manager'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($result['items'] as $download) : ?>
                            <?php
                            $download_id = (int) ($download['id'] ?? 0);
                            $edit_link = admin_url('admin.php?page=' . self::MENU_SLUG . '&action=edit&id=' . $download_id);
                            $delete_link = wp_nonce_url(
                                admin_url('admin-post.php?action=gtdm_delete_download&id=' . $download_id),
                                'gtdm_delete_download_' . $download_id
                            );
                            ?>
                            <tr>
                                <td><?php echo esc_html((string) $download_id); ?></td>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url($edit_link); ?>">
                                            <?php echo esc_html((string) ($download['title'] ?? '')); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <a href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Edit', 'gt-downloads-manager'); ?></a>
                                        |
                                        <a href="<?php echo esc_url($delete_link); ?>" onclick="return confirm('<?php echo esc_js(__('Delete this download?', 'gt-downloads-manager')); ?>');">
                                            <?php esc_html_e('Delete', 'gt-downloads-manager'); ?>
                                        </a>
                                    </div>
                                </td>
                                <td><?php echo esc_html((string) ($download['file_source'] ?? 'media')); ?></td>
                                <td><?php echo esc_html(self::term_display((string) ($download['categories'] ?? ''))); ?></td>
                                <td><?php echo esc_html(self::term_display((string) ($download['tags'] ?? ''))); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) ($download['download_count'] ?? 0))); ?></td>
                                <td><?php echo esc_html((string) ($download['status'] ?? 'publish')); ?></td>
                                <td><code>[gtdm_download id="<?php echo esc_html((string) $download_id); ?>"]</code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php
            $total_pages = (int) ($result['total_pages'] ?? 0);

            if ($total_pages > 1) {
                $base_url = remove_query_arg('paged');
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%', $base_url),
                    'format' => '',
                    'current' => $page,
                    'total' => $total_pages,
                ]);
                echo '</div></div>';
            }
            ?>
        </div>
        <?php
    }

    private static function render_form(int $download_id = 0): void {
        $download = $download_id > 0 ? DownloadRepository::find($download_id) : null;

        if ($download_id > 0 && ! is_array($download)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Download not found.', 'gt-downloads-manager') . '</p></div>';
            self::render_list();
            return;
        }

        $download = is_array($download) ? $download : [
            'id' => 0,
            'title' => '',
            'slug' => '',
            'description' => '',
            'excerpt' => '',
            'featured_image_id' => 0,
            'file_source' => 'media',
            'file_id' => 0,
            'direct_url' => '',
            'categories' => '',
            'tags' => '',
            'status' => 'publish',
        ];

        $featured_image_id = absint((string) $download['featured_image_id']);
        $file_id = absint((string) $download['file_id']);

        $featured_label = self::attachment_label($featured_image_id, __('No image selected', 'gt-downloads-manager'));
        $file_label = self::attachment_label($file_id, __('No file selected', 'gt-downloads-manager'));

        $featured_preview = $featured_image_id > 0 ? wp_get_attachment_image($featured_image_id, 'medium') : '';

        $categories = DownloadRepository::aggregate_terms('categories', '');
        $tags = DownloadRepository::aggregate_terms('tags', '');
        ?>
        <div class="wrap gtdm-admin-page gtdm-admin-form-page">
            <div class="gtdm-admin-shell">
                <header class="gtdm-admin-hero">
                    <h1>
                        <?php
                        echo $download_id > 0
                            ? esc_html__('Edit Download', 'gt-downloads-manager')
                            : esc_html__('Add New Download', 'gt-downloads-manager');
                        ?>
                    </h1>
                    <p>
                        <?php esc_html_e('Use media pickers, clean term labels, and save fast from one screen.', 'gt-downloads-manager'); ?>
                    </p>
                </header>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gtdm-admin-form">
                    <input type="hidden" name="action" value="gtdm_save_download" />
                    <input type="hidden" name="id" value="<?php echo esc_attr((string) $download['id']); ?>" />
                    <?php wp_nonce_field('gtdm_save_download', 'gtdm_nonce'); ?>

                    <div class="gtdm-admin-grid">
                        <section class="gtdm-panel">
                            <h2><?php esc_html_e('Content', 'gt-downloads-manager'); ?></h2>

                            <div class="gtdm-field">
                                <label for="gtdm_title"><?php esc_html_e('Title', 'gt-downloads-manager'); ?></label>
                                <input id="gtdm_title" name="title" type="text" required value="<?php echo esc_attr((string) $download['title']); ?>" />
                            </div>

                            <div class="gtdm-field">
                                <label for="gtdm_slug"><?php esc_html_e('Slug', 'gt-downloads-manager'); ?></label>
                                <input id="gtdm_slug" name="slug" type="text" value="<?php echo esc_attr((string) $download['slug']); ?>" />
                            </div>

                            <div class="gtdm-field">
                                <label for="gtdm_excerpt"><?php esc_html_e('Excerpt', 'gt-downloads-manager'); ?></label>
                                <textarea id="gtdm_excerpt" name="excerpt" rows="4"><?php echo esc_textarea((string) $download['excerpt']); ?></textarea>
                            </div>

                            <div class="gtdm-field">
                                <label for="gtdm_description"><?php esc_html_e('Description', 'gt-downloads-manager'); ?></label>
                                <textarea id="gtdm_description" name="description" rows="9"><?php echo esc_textarea((string) $download['description']); ?></textarea>
                            </div>
                        </section>

                        <section class="gtdm-panel">
                            <h2><?php esc_html_e('Media and Delivery', 'gt-downloads-manager'); ?></h2>

                            <div class="gtdm-field">
                                <label><?php esc_html_e('Featured Image', 'gt-downloads-manager'); ?></label>
                                <input id="gtdm_featured_image_id" name="featured_image_id" type="hidden" value="<?php echo esc_attr((string) $featured_image_id); ?>" />
                                <div class="gtdm-picker">
                                    <p id="gtdm_featured_image_label" class="gtdm-picker-label" data-empty-label="<?php echo esc_attr(__('No image selected', 'gt-downloads-manager')); ?>">
                                        <?php echo esc_html($featured_label); ?>
                                    </p>
                                    <div id="gtdm_featured_image_preview" class="gtdm-picker-preview">
                                        <?php echo $featured_preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </div>
                                    <div class="gtdm-picker-actions">
                                        <button
                                            type="button"
                                            class="button button-secondary gtdm-open-media"
                                            data-target-input="gtdm_featured_image_id"
                                            data-target-label="gtdm_featured_image_label"
                                            data-target-preview="gtdm_featured_image_preview"
                                            data-library-type="image"
                                            data-frame-title="<?php echo esc_attr(__('Choose featured image', 'gt-downloads-manager')); ?>"
                                            data-button-text="<?php echo esc_attr(__('Use featured image', 'gt-downloads-manager')); ?>"
                                        >
                                            <?php esc_html_e('Choose Image', 'gt-downloads-manager'); ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="button-link-delete gtdm-clear-media"
                                            data-target-input="gtdm_featured_image_id"
                                            data-target-label="gtdm_featured_image_label"
                                            data-target-preview="gtdm_featured_image_preview"
                                        >
                                            <?php esc_html_e('Remove', 'gt-downloads-manager'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="gtdm-field">
                                <label><?php esc_html_e('File Source', 'gt-downloads-manager'); ?></label>
                                <div class="gtdm-inline-radio">
                                    <label>
                                        <input type="radio" name="file_source" value="media" <?php checked((string) $download['file_source'], 'media'); ?> />
                                        <?php esc_html_e('Media file', 'gt-downloads-manager'); ?>
                                    </label>
                                    <label>
                                        <input type="radio" name="file_source" value="direct" <?php checked((string) $download['file_source'], 'direct'); ?> />
                                        <?php esc_html_e('Direct URL', 'gt-downloads-manager'); ?>
                                    </label>
                                </div>
                            </div>

                            <div class="gtdm-source-panel gtdm-source-media">
                                <input id="gtdm_file_id" name="file_id" type="hidden" value="<?php echo esc_attr((string) $file_id); ?>" />
                                <p id="gtdm_file_label" class="gtdm-picker-label" data-empty-label="<?php echo esc_attr(__('No file selected', 'gt-downloads-manager')); ?>">
                                    <?php echo esc_html($file_label); ?>
                                </p>
                                <div class="gtdm-picker-actions">
                                    <button
                                        type="button"
                                        class="button button-secondary gtdm-open-media"
                                        data-target-input="gtdm_file_id"
                                        data-target-label="gtdm_file_label"
                                        data-frame-title="<?php echo esc_attr(__('Choose file', 'gt-downloads-manager')); ?>"
                                        data-button-text="<?php echo esc_attr(__('Use this file', 'gt-downloads-manager')); ?>"
                                        data-force-source="media"
                                    >
                                        <?php esc_html_e('Choose File', 'gt-downloads-manager'); ?>
                                    </button>
                                    <button
                                        type="button"
                                        class="button-link-delete gtdm-clear-media"
                                        data-target-input="gtdm_file_id"
                                        data-target-label="gtdm_file_label"
                                    >
                                        <?php esc_html_e('Remove', 'gt-downloads-manager'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="gtdm-source-panel gtdm-source-direct">
                                <label for="gtdm_direct_url"><?php esc_html_e('Direct URL', 'gt-downloads-manager'); ?></label>
                                <input id="gtdm_direct_url" name="direct_url" type="url" value="<?php echo esc_attr((string) $download['direct_url']); ?>" />
                            </div>
                        </section>

                        <section class="gtdm-panel">
                            <h2><?php esc_html_e('Classification', 'gt-downloads-manager'); ?></h2>

                            <div class="gtdm-field">
                                <label for="gtdm_categories"><?php esc_html_e('Categories', 'gt-downloads-manager'); ?></label>
                                <input id="gtdm_categories" list="gtdm-categories-list" name="categories" type="text" value="<?php echo esc_attr((string) $download['categories']); ?>" />
                                <p class="description"><?php esc_html_e('Comma-separated slugs, for example: plugins,seo-tools', 'gt-downloads-manager'); ?></p>
                                <datalist id="gtdm-categories-list">
                                    <?php foreach ($categories as $term) : ?>
                                        <option value="<?php echo esc_attr((string) $term['slug']); ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="gtdm-field">
                                <label for="gtdm_tags"><?php esc_html_e('Tags', 'gt-downloads-manager'); ?></label>
                                <input id="gtdm_tags" list="gtdm-tags-list" name="tags" type="text" value="<?php echo esc_attr((string) $download['tags']); ?>" />
                                <p class="description"><?php esc_html_e('Comma-separated slugs, for example: free,pdf', 'gt-downloads-manager'); ?></p>
                                <datalist id="gtdm-tags-list">
                                    <?php foreach ($tags as $term) : ?>
                                        <option value="<?php echo esc_attr((string) $term['slug']); ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="gtdm-field">
                                <label for="gtdm_status"><?php esc_html_e('Status', 'gt-downloads-manager'); ?></label>
                                <select id="gtdm_status" name="status">
                                    <option value="publish" <?php selected((string) $download['status'], 'publish'); ?>><?php esc_html_e('Publish', 'gt-downloads-manager'); ?></option>
                                    <option value="draft" <?php selected((string) $download['status'], 'draft'); ?>><?php esc_html_e('Draft', 'gt-downloads-manager'); ?></option>
                                </select>
                            </div>
                        </section>
                    </div>

                    <div class="gtdm-action-row">
                        <button type="submit" class="button button-primary button-hero" name="gtdm_submit_action" value="save">
                            <?php esc_html_e('Save Download', 'gt-downloads-manager'); ?>
                        </button>
                        <button type="submit" class="button button-secondary button-hero" name="gtdm_submit_action" value="save_add_another">
                            <?php esc_html_e('Save & Add Another', 'gt-downloads-manager'); ?>
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . self::MENU_SLUG)); ?>" class="button"><?php esc_html_e('Cancel', 'gt-downloads-manager'); ?></a>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    public static function handle_save_download(): void {
        if (! current_user_can('edit_others_posts')) {
            wp_die(esc_html__('Permission denied.', 'gt-downloads-manager'));
        }

        check_admin_referer('gtdm_save_download', 'gtdm_nonce');

        $data = [
            'id' => isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0,
            'title' => isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '',
            'slug' => isset($_POST['slug']) ? sanitize_title(wp_unslash($_POST['slug'])) : '',
            'excerpt' => isset($_POST['excerpt']) ? sanitize_textarea_field(wp_unslash($_POST['excerpt'])) : '',
            'description' => isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '',
            'featured_image_id' => isset($_POST['featured_image_id']) ? absint(wp_unslash($_POST['featured_image_id'])) : 0,
            'file_source' => isset($_POST['file_source']) ? sanitize_key(wp_unslash($_POST['file_source'])) : 'media',
            'file_id' => isset($_POST['file_id']) ? absint(wp_unslash($_POST['file_id'])) : 0,
            'direct_url' => isset($_POST['direct_url']) ? esc_url_raw(wp_unslash($_POST['direct_url'])) : '',
            'categories' => isset($_POST['categories']) ? sanitize_text_field(wp_unslash($_POST['categories'])) : '',
            'tags' => isset($_POST['tags']) ? sanitize_text_field(wp_unslash($_POST['tags'])) : '',
            'status' => isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : 'publish',
        ];

        if ($data['title'] === '') {
            self::redirect_with_notice('error_title');
        }

        if ($data['file_source'] === 'media' && $data['file_id'] <= 0) {
            self::redirect_with_notice('error_file');
        }

        if ($data['file_source'] === 'direct' && $data['direct_url'] === '') {
            self::redirect_with_notice('error_url');
        }

        $saved_id = DownloadRepository::save($data);

        if (! $saved_id) {
            self::redirect_with_notice('error_save');
        }

        $submit_action = isset($_POST['gtdm_submit_action'])
            ? sanitize_key((string) wp_unslash($_POST['gtdm_submit_action']))
            : 'save';

        if ($submit_action === 'save_add_another') {
            wp_safe_redirect(admin_url('admin.php?page=' . self::MENU_SLUG . '&action=new&gtdm_notice=saved'));
            exit;
        }

        wp_safe_redirect(admin_url('admin.php?page=' . self::MENU_SLUG . '&action=edit&id=' . (int) $saved_id . '&gtdm_notice=saved'));
        exit;
    }

    public static function handle_delete_download(): void {
        if (! current_user_can('edit_others_posts')) {
            wp_die(esc_html__('Permission denied.', 'gt-downloads-manager'));
        }

        $download_id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;

        if ($download_id <= 0) {
            self::redirect_with_notice('error_delete');
        }

        check_admin_referer('gtdm_delete_download_' . $download_id);

        $deleted = DownloadRepository::delete($download_id);

        if (! $deleted) {
            self::redirect_with_notice('error_delete');
        }

        wp_safe_redirect(admin_url('admin.php?page=' . self::MENU_SLUG . '&gtdm_notice=deleted'));
        exit;
    }

    public static function render_categories_page(): void {
        self::render_term_page('category');
    }

    public static function render_tags_page(): void {
        self::render_term_page('tag');
    }

    private static function render_term_page(string $type): void {
        if (! current_user_can('edit_others_posts')) {
            wp_die(esc_html__('You are not allowed to access this page.', 'gt-downloads-manager'));
        }

        $column = self::column_for_term_type($type);

        if ($column === '') {
            return;
        }

        $title = $type === 'category'
            ? __('Download Categories', 'gt-downloads-manager')
            : __('Download Tags', 'gt-downloads-manager');

        $input_label = $type === 'category'
            ? __('Category name', 'gt-downloads-manager')
            : __('Tag name', 'gt-downloads-manager');

        $add_button_label = $type === 'category'
            ? __('Add Category', 'gt-downloads-manager')
            : __('Add Tag', 'gt-downloads-manager');

        $terms = DownloadRepository::aggregate_terms($column, '');
        ?>
        <div class="wrap gtdm-admin-page gtdm-terms-page">
            <h1><?php echo esc_html($title); ?></h1>

            <div class="gtdm-term-layout">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gtdm-term-add-form">
                    <input type="hidden" name="action" value="gtdm_save_term" />
                    <input type="hidden" name="mode" value="add" />
                    <input type="hidden" name="term_type" value="<?php echo esc_attr($type); ?>" />
                    <?php wp_nonce_field('gtdm_save_term_' . $type, 'gtdm_term_nonce'); ?>

                    <label for="gtdm_term_name_<?php echo esc_attr($type); ?>"><?php echo esc_html($input_label); ?></label>
                    <input id="gtdm_term_name_<?php echo esc_attr($type); ?>" name="term_name" type="text" required />

                    <button type="submit" class="button button-primary"><?php echo esc_html($add_button_label); ?></button>
                </form>

                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Name', 'gt-downloads-manager'); ?></th>
                            <th><?php esc_html_e('Slug', 'gt-downloads-manager'); ?></th>
                            <th><?php esc_html_e('Count', 'gt-downloads-manager'); ?></th>
                            <th><?php esc_html_e('Actions', 'gt-downloads-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($terms === []) : ?>
                            <tr>
                                <td colspan="4"><?php esc_html_e('No terms yet.', 'gt-downloads-manager'); ?></td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($terms as $term) : ?>
                                <?php
                                $slug = isset($term['slug']) ? sanitize_title((string) $term['slug']) : '';
                                $name = isset($term['name']) ? (string) $term['name'] : '';
                                $count = isset($term['count']) ? (int) $term['count'] : 0;
                                $filter_key = $type === 'category' ? 'category' : 'tag';
                                $filter_link = add_query_arg([
                                    'page' => self::MENU_SLUG,
                                    $filter_key => $slug,
                                ], admin_url('admin.php'));
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($name); ?></strong>
                                        <div class="row-actions">
                                            <a href="<?php echo esc_url($filter_link); ?>"><?php esc_html_e('View downloads', 'gt-downloads-manager'); ?></a>
                                        </div>
                                    </td>
                                    <td><code><?php echo esc_html($slug); ?></code></td>
                                    <td><?php echo esc_html(number_format_i18n($count)); ?></td>
                                    <td>
                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gtdm-term-inline-form">
                                            <input type="hidden" name="action" value="gtdm_save_term" />
                                            <input type="hidden" name="mode" value="rename" />
                                            <input type="hidden" name="term_type" value="<?php echo esc_attr($type); ?>" />
                                            <input type="hidden" name="old_slug" value="<?php echo esc_attr($slug); ?>" />
                                            <?php wp_nonce_field('gtdm_save_term_' . $type . '_' . $slug, 'gtdm_term_nonce'); ?>
                                            <input type="text" name="term_name" value="<?php echo esc_attr($name); ?>" required />
                                            <button type="submit" class="button button-secondary"><?php esc_html_e('Rename', 'gt-downloads-manager'); ?></button>
                                        </form>

                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gtdm-term-inline-form gtdm-term-inline-delete">
                                            <input type="hidden" name="action" value="gtdm_delete_term" />
                                            <input type="hidden" name="term_type" value="<?php echo esc_attr($type); ?>" />
                                            <input type="hidden" name="term_slug" value="<?php echo esc_attr($slug); ?>" />
                                            <?php wp_nonce_field('gtdm_delete_term_' . $type . '_' . $slug, 'gtdm_term_nonce'); ?>
                                            <button type="submit" class="button-link-delete gtdm-delete-term"><?php esc_html_e('Delete', 'gt-downloads-manager'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public static function handle_save_term(): void {
        if (! current_user_can('edit_others_posts')) {
            wp_die(esc_html__('Permission denied.', 'gt-downloads-manager'));
        }

        $type = isset($_POST['term_type']) ? sanitize_key((string) wp_unslash($_POST['term_type'])) : '';
        $mode = isset($_POST['mode']) ? sanitize_key((string) wp_unslash($_POST['mode'])) : 'add';
        $column = self::column_for_term_type($type);

        if ($column === '') {
            self::redirect_with_notice('error_term');
        }

        if ($mode === 'rename') {
            $old_slug = isset($_POST['old_slug']) ? sanitize_title((string) wp_unslash($_POST['old_slug'])) : '';
            check_admin_referer('gtdm_save_term_' . $type . '_' . $old_slug, 'gtdm_term_nonce');

            $term_name = isset($_POST['term_name']) ? sanitize_text_field(wp_unslash($_POST['term_name'])) : '';
            $new_slug = sanitize_title($term_name);

            if ($old_slug === '' || $new_slug === '' || ! DownloadRepository::rename_term($column, $old_slug, $new_slug)) {
                self::redirect_with_notice('error_term', self::page_for_term_type($type));
            }

            self::redirect_with_notice('term_saved', self::page_for_term_type($type));
        }

        check_admin_referer('gtdm_save_term_' . $type, 'gtdm_term_nonce');

        $term_name = isset($_POST['term_name']) ? sanitize_text_field(wp_unslash($_POST['term_name'])) : '';
        $slug = sanitize_title($term_name);

        if ($slug === '' || ! DownloadRepository::register_term($column, $slug)) {
            self::redirect_with_notice('error_term', self::page_for_term_type($type));
        }

        self::redirect_with_notice('term_saved', self::page_for_term_type($type));
    }

    public static function handle_delete_term(): void {
        if (! current_user_can('edit_others_posts')) {
            wp_die(esc_html__('Permission denied.', 'gt-downloads-manager'));
        }

        $type = isset($_POST['term_type']) ? sanitize_key((string) wp_unslash($_POST['term_type'])) : '';
        $slug = isset($_POST['term_slug']) ? sanitize_title((string) wp_unslash($_POST['term_slug'])) : '';
        $column = self::column_for_term_type($type);

        if ($column === '' || $slug === '') {
            self::redirect_with_notice('error_term');
        }

        check_admin_referer('gtdm_delete_term_' . $type . '_' . $slug, 'gtdm_term_nonce');

        if (! DownloadRepository::remove_term($column, $slug)) {
            self::redirect_with_notice('error_term', self::page_for_term_type($type));
        }

        self::redirect_with_notice('term_deleted', self::page_for_term_type($type));
    }

    public static function render_docs_page(): void {
        ?>
        <div class="wrap gtdm-admin-page gtdm-docs-page">
            <h1><?php esc_html_e('GT Downloads Manager Docs', 'gt-downloads-manager'); ?></h1>

            <p><?php esc_html_e('Data is stored in a dedicated custom table and exposed through shortcodes and REST.', 'gt-downloads-manager'); ?></p>

            <h2><?php esc_html_e('Shortcodes', 'gt-downloads-manager'); ?></h2>
            <p><code>[gtdm_download id="123" image="medium"]</code></p>
            <p><code>[gtdm_downloads category="" tag="" search="" sort="newest" per_page="12" page="1" layout="grid" filters="1"]</code></p>

            <h2><?php esc_html_e('REST API', 'gt-downloads-manager'); ?></h2>
            <ul>
                <li><code>GET /wp-json/gtdm/v2/downloads</code></li>
                <li><code>GET /wp-json/gtdm/v2/downloads/{id}</code></li>
                <li><code>GET /wp-json/gtdm/v2/downloads/search?search=keyword</code></li>
                <li><code>GET /wp-json/gtdm/v2/terms/categories?search=slug</code></li>
                <li><code>GET /wp-json/gtdm/v2/terms/tags?search=slug</code></li>
                <li><code>POST /wp-json/gtdm/v2/downloads/{id}/track</code></li>
            </ul>

            <h2><?php esc_html_e('Query Parameters', 'gt-downloads-manager'); ?></h2>
            <p><code>gtdm_s</code>, <code>gtdm_cat</code>, <code>gtdm_tag</code>, <code>gtdm_sort</code>, <code>gtdm_page</code></p>
        </div>
        <?php
    }

    public static function render_notices(): void {
        if (! isset($_GET['gtdm_notice'])) {
            return;
        }

        if (! self::is_plugin_admin_page()) {
            return;
        }

        $notice = sanitize_key((string) wp_unslash($_GET['gtdm_notice']));

        $messages = [
            'saved' => ['success', __('Download saved.', 'gt-downloads-manager')],
            'deleted' => ['success', __('Download deleted.', 'gt-downloads-manager')],
            'term_saved' => ['success', __('Term saved.', 'gt-downloads-manager')],
            'term_deleted' => ['success', __('Term deleted.', 'gt-downloads-manager')],
            'error_title' => ['error', __('Title is required.', 'gt-downloads-manager')],
            'error_file' => ['error', __('Media source requires a selected file.', 'gt-downloads-manager')],
            'error_url' => ['error', __('Direct source requires a direct URL.', 'gt-downloads-manager')],
            'error_save' => ['error', __('Unable to save download.', 'gt-downloads-manager')],
            'error_delete' => ['error', __('Unable to delete download.', 'gt-downloads-manager')],
            'error_term' => ['error', __('Unable to save term.', 'gt-downloads-manager')],
        ];

        if (! isset($messages[$notice])) {
            return;
        }

        [$type, $message] = $messages[$notice];

        printf(
            '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
            esc_attr($type),
            esc_html($message)
        );
    }

    private static function redirect_with_notice(string $notice, string $page = self::MENU_SLUG): void {
        wp_safe_redirect(admin_url('admin.php?page=' . $page . '&gtdm_notice=' . $notice));
        exit;
    }

    private static function term_display(string $csv): string {
        $terms = DownloadRepository::terms_from_csv($csv);

        if ($terms === []) {
            return '';
        }

        return implode(', ', array_map(static fn(string $slug): string => ucwords(str_replace('-', ' ', $slug)), $terms));
    }

    private static function is_plugin_admin_page(): bool {
        if (! is_admin()) {
            return false;
        }

        $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';

        return in_array($page, [self::MENU_SLUG, self::PAGE_DOCS, self::PAGE_CATEGORIES, self::PAGE_TAGS], true);
    }

    private static function column_for_term_type(string $type): string {
        if ($type === 'category') {
            return 'categories';
        }

        if ($type === 'tag') {
            return 'tags';
        }

        return '';
    }

    private static function page_for_term_type(string $type): string {
        return $type === 'category' ? self::PAGE_CATEGORIES : self::PAGE_TAGS;
    }

    private static function attachment_label(int $attachment_id, string $fallback): string {
        if ($attachment_id <= 0) {
            return $fallback;
        }

        $title = (string) get_the_title($attachment_id);

        if ($title !== '') {
            return $title;
        }

        $file = get_attached_file($attachment_id);

        if (is_string($file) && $file !== '') {
            return wp_basename($file);
        }

        $url = wp_get_attachment_url($attachment_id);

        return is_string($url) && $url !== '' ? $url : $fallback;
    }
}
