<?php

declare(strict_types=1);

namespace GTDownloadsManager\Frontend;

use GTDownloadsManager\Domain\DownloadRepository;
use GTDownloadsManager\Domain\DownloadService;
use GTDownloadsManager\Domain\QueryService;

class Renderer {
    public static function render_single(int $download_id, string $image = 'medium', string $context = 'shortcode'): string {
        $download = DownloadRepository::find($download_id);

        if (! is_array($download) || ($download['status'] ?? '') !== 'publish') {
            return '';
        }

        self::enqueue_assets($context, false);

        return '<div class="gtdm-root gtdm-root-single">' . self::render_card($download, $image) . '</div>';
    }

    public static function render_downloads(array $input = [], bool $merge_request = true, string $context = 'shortcode'): string {
        self::enqueue_assets($context, true);

        $built = QueryService::build_query_args($input, $merge_request);
        $state = $built['state'];
        $result = DownloadRepository::query($built['query_args']);

        $output = sprintf(
            '<div class="gtdm-root" data-gtdm-root data-layout="%1$s" data-per-page="%2$d">',
            esc_attr((string) $state['layout']),
            (int) $state['per_page']
        );

        if ((int) $state['filters'] === 1) {
            $output .= self::render_filters_form($state);
        }

        $output .= '<div class="gtdm-results" data-gtdm-results>';
        $output .= self::render_results_markup($result, $state);
        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * @param array<string, mixed> $state
     */
    public static function render_filters_form(array $state): string {
        $categories = DownloadRepository::aggregate_terms('categories');
        $tags = DownloadRepository::aggregate_terms('tags');

        $sort_options = [
            'newest' => __('Newest first', 'gt-downloads-manager'),
            'oldest' => __('Oldest first', 'gt-downloads-manager'),
            'popular' => __('Most downloaded', 'gt-downloads-manager'),
            'title_asc' => __('Title A-Z', 'gt-downloads-manager'),
            'title_desc' => __('Title Z-A', 'gt-downloads-manager'),
        ];

        ob_start();
        ?>
        <form class="gtdm-filters" data-gtdm-filters method="get" action="">
            <div class="gtdm-filter-row">
                <label for="gtdm_s"><?php esc_html_e('Search', 'gt-downloads-manager'); ?></label>
                <input
                    id="gtdm_s"
                    name="gtdm_s"
                    type="search"
                    value="<?php echo esc_attr((string) $state['search']); ?>"
                    placeholder="<?php esc_attr_e('Search downloads...', 'gt-downloads-manager'); ?>"
                />
            </div>

            <div class="gtdm-filter-row">
                <label for="gtdm_cat"><?php esc_html_e('Category', 'gt-downloads-manager'); ?></label>
                <select id="gtdm_cat" name="gtdm_cat">
                    <option value=""><?php esc_html_e('All categories', 'gt-downloads-manager'); ?></option>
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?php echo esc_attr((string) $category['slug']); ?>" <?php selected($state['category'], $category['slug']); ?>>
                            <?php echo esc_html((string) $category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="gtdm-filter-row">
                <label for="gtdm_tag"><?php esc_html_e('Tag', 'gt-downloads-manager'); ?></label>
                <select id="gtdm_tag" name="gtdm_tag">
                    <option value=""><?php esc_html_e('All tags', 'gt-downloads-manager'); ?></option>
                    <?php foreach ($tags as $tag) : ?>
                        <option value="<?php echo esc_attr((string) $tag['slug']); ?>" <?php selected($state['tag'], $tag['slug']); ?>>
                            <?php echo esc_html((string) $tag['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="gtdm-filter-row">
                <label for="gtdm_sort"><?php esc_html_e('Sort', 'gt-downloads-manager'); ?></label>
                <select id="gtdm_sort" name="gtdm_sort">
                    <?php foreach ($sort_options as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($state['sort'], $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <input type="hidden" name="gtdm_page" value="1" />
            <button type="submit" class="gtdm-filter-submit"><?php esc_html_e('Apply', 'gt-downloads-manager'); ?></button>
        </form>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * @param array{items: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int} $result
     * @param array<string, mixed> $state
     */
    public static function render_results_markup(array $result, array $state): string {
        $items = $result['items'] ?? [];

        if ($items === []) {
            return '<p class="gtdm-no-results">' . esc_html__('No downloads found.', 'gt-downloads-manager') . '</p>';
        }

        $layout = (string) $state['layout'];
        $image = isset($state['image']) && is_string($state['image']) ? sanitize_key($state['image']) : 'medium';

        ob_start();

        if ($layout === 'table') {
            ?>
            <div class="gtdm-table-wrap">
                <table class="gtdm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Title', 'gt-downloads-manager'); ?></th>
                            <th><?php esc_html_e('Category', 'gt-downloads-manager'); ?></th>
                            <th><?php esc_html_e('Tags', 'gt-downloads-manager'); ?></th>
                            <th><?php esc_html_e('Downloads', 'gt-downloads-manager'); ?></th>
                            <th><?php esc_html_e('Action', 'gt-downloads-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $download) : ?>
                            <?php
                            $download_id = (int) ($download['id'] ?? 0);
                            $title = (string) ($download['title'] ?? '');
                            $categories = self::term_list($download, 'categories');
                            $tags = self::term_list($download, 'tags');
                            $count = (int) ($download['download_count'] ?? 0);
                            ?>
                            <tr>
                                <td data-label="<?php esc_attr_e('Title', 'gt-downloads-manager'); ?>">
                                    <strong><?php echo esc_html($title); ?></strong>
                                </td>
                                <td data-label="<?php esc_attr_e('Category', 'gt-downloads-manager'); ?>">
                                    <?php echo esc_html($categories); ?>
                                </td>
                                <td data-label="<?php esc_attr_e('Tags', 'gt-downloads-manager'); ?>">
                                    <?php echo esc_html($tags); ?>
                                </td>
                                <td data-label="<?php esc_attr_e('Downloads', 'gt-downloads-manager'); ?>">
                                    <?php echo esc_html(number_format_i18n($count)); ?>
                                </td>
                                <td data-label="<?php esc_attr_e('Action', 'gt-downloads-manager'); ?>">
                                    <a class="gtdm-btn" href="<?php echo esc_url(DownloadService::get_download_url($download_id)); ?>">
                                        <?php esc_html_e('Download', 'gt-downloads-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
        } else {
            echo '<div class="gtdm-grid">';

            foreach ($items as $download) {
                echo self::render_card($download, $image); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

            echo '</div>';
        }

        echo self::render_pagination($result, $state); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $download
     */
    private static function render_card(array $download, string $image): string {
        $download_id = (int) ($download['id'] ?? 0);
        $download_url = DownloadService::get_download_url($download_id);
        $download_count = (int) ($download['download_count'] ?? 0);
        $featured_image_id = (int) ($download['featured_image_id'] ?? 0);

        $thumbnail = $featured_image_id > 0
            ? wp_get_attachment_image(
                $featured_image_id,
                $image,
                false,
                [
                    'class' => 'gtdm-card-image',
                    'loading' => 'lazy',
                ]
            )
            : '';

        $description = isset($download['description']) ? (string) $download['description'] : '';
        $excerpt = isset($download['excerpt']) ? (string) $download['excerpt'] : '';

        if ($excerpt === '') {
            $excerpt = wp_trim_words(wp_strip_all_tags($description), 24);
        }

        $categories = self::term_list($download, 'categories');
        $tags = self::term_list($download, 'tags');

        ob_start();
        ?>
        <article class="gtdm-card" data-download-id="<?php echo esc_attr((string) $download_id); ?>">
            <?php if ($thumbnail !== '') : ?>
                <div class="gtdm-card-media"><?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            <?php endif; ?>

            <div class="gtdm-card-body">
                <h3 class="gtdm-card-title"><?php echo esc_html((string) ($download['title'] ?? '')); ?></h3>

                <?php if ($categories !== '') : ?>
                    <div class="gtdm-card-meta gtdm-card-categories"><?php echo esc_html($categories); ?></div>
                <?php endif; ?>

                <?php if ($excerpt !== '') : ?>
                    <div class="gtdm-card-excerpt"><?php echo esc_html($excerpt); ?></div>
                <?php endif; ?>

                <?php if ($tags !== '') : ?>
                    <div class="gtdm-card-meta gtdm-card-tags"><?php echo esc_html($tags); ?></div>
                <?php endif; ?>

                <div class="gtdm-card-footer">
                    <a class="gtdm-btn" href="<?php echo esc_url($download_url); ?>">
                        <?php esc_html_e('Download', 'gt-downloads-manager'); ?>
                    </a>
                    <span class="gtdm-download-count">
                        <?php
                        printf(
                            /* translators: %s: number of downloads */
                            esc_html__('%s downloads', 'gt-downloads-manager'),
                            esc_html(number_format_i18n($download_count))
                        );
                        ?>
                    </span>
                </div>
            </div>
        </article>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * @param array{items: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int} $result
     * @param array<string, mixed> $state
     */
    private static function render_pagination(array $result, array $state): string {
        $total_pages = isset($result['total_pages']) ? (int) $result['total_pages'] : 0;

        if ($total_pages <= 1) {
            return '';
        }

        $current_page = isset($result['page']) ? max(1, (int) $result['page']) : 1;
        $base_url = isset($state['base_url']) && is_string($state['base_url']) && $state['base_url'] !== ''
            ? remove_query_arg('gtdm_page', $state['base_url'])
            : remove_query_arg('gtdm_page', self::current_url());

        $links = paginate_links([
            'base' => add_query_arg('gtdm_page', '%#%', $base_url),
            'format' => '',
            'current' => $current_page,
            'total' => $total_pages,
            'type' => 'array',
            'prev_text' => esc_html__('Prev', 'gt-downloads-manager'),
            'next_text' => esc_html__('Next', 'gt-downloads-manager'),
        ]);

        if (! is_array($links)) {
            return '';
        }

        $markup = '<nav class="gtdm-pagination" aria-label="' . esc_attr__('Downloads pagination', 'gt-downloads-manager') . '"><ul>';

        foreach ($links as $link) {
            $markup .= '<li>' . $link . '</li>';
        }

        $markup .= '</ul></nav>';

        return $markup;
    }

    private static function current_url(): string {
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $path = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
        $path = is_string($path) ? $path : '/';

        return esc_url_raw((is_ssl() ? 'https://' : 'http://') . $host . $path);
    }

    /**
     * @param array<string, mixed> $download
     */
    private static function term_list(array $download, string $key): string {
        if (! isset($download[$key]) || ! is_string($download[$key])) {
            return '';
        }

        $names = array_map(
            static fn(string $slug): string => ucwords(str_replace(['-', '_'], ' ', $slug)),
            DownloadRepository::terms_from_csv($download[$key])
        );

        return implode(', ', $names);
    }

    private static function enqueue_assets(string $context, bool $needs_script): void {
        if ($context === 'block') {
            Assets::enqueue_block_styles();

            if ($needs_script) {
                Assets::enqueue_script();
            }

            return;
        }

        Assets::enqueue_frontend();
    }
}
