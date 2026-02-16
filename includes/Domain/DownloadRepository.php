<?php

declare(strict_types=1);

namespace GTDownloadsManager\Domain;

class DownloadRepository {
    private const TABLE_SUFFIX = 'gtdm_downloads';

    public static function table_name(): string {
        global $wpdb;

        return $wpdb->prefix . self::TABLE_SUFFIX;
    }

    public static function create_table(): void {
        global $wpdb;

        $table_name = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(200) NOT NULL DEFAULT '',
            description LONGTEXT NULL,
            excerpt TEXT NULL,
            featured_image_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            file_source VARCHAR(20) NOT NULL DEFAULT 'media',
            file_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            direct_url TEXT NULL,
            categories TEXT NULL,
            tags TEXT NULL,
            download_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'publish',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY slug (slug)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * @param array<string, mixed> $args
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
     */
    public static function query(array $args = []): array {
        global $wpdb;

        $defaults = [
            'search' => '',
            'category' => '',
            'tag' => '',
            'sort' => 'newest',
            'page' => 1,
            'per_page' => 12,
            'status' => 'publish',
        ];

        $args = wp_parse_args($args, $defaults);

        $status = isset($args['status']) && is_string($args['status']) ? sanitize_key($args['status']) : 'publish';
        $search = isset($args['search']) && is_string($args['search']) ? sanitize_text_field($args['search']) : '';
        $category = isset($args['category']) && is_string($args['category']) ? sanitize_title($args['category']) : '';
        $tag = isset($args['tag']) && is_string($args['tag']) ? sanitize_title($args['tag']) : '';
        $sort = isset($args['sort']) && is_string($args['sort']) ? sanitize_key($args['sort']) : 'newest';
        $page = isset($args['page']) ? max(1, absint($args['page'])) : 1;
        $per_page = isset($args['per_page']) ? max(1, min(50, absint($args['per_page']))) : 12;

        $where = [];
        $params = [];

        if ($status !== '') {
            $where[] = 'status = %s';
            $params[] = $status;
        }

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = '(title LIKE %s OR description LIKE %s OR excerpt LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($category !== '') {
            [$category_sql, $category_params] = self::build_csv_term_where('categories', $category, $wpdb);
            $where[] = $category_sql;
            array_push($params, ...$category_params);
        }

        if ($tag !== '') {
            [$tag_sql, $tag_params] = self::build_csv_term_where('tags', $tag, $wpdb);
            $where[] = $tag_sql;
            array_push($params, ...$tag_params);
        }

        $where_clause = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $count_sql = 'SELECT COUNT(*) FROM ' . self::table_name() . $where_clause;
        $count_query = $params === [] ? $count_sql : $wpdb->prepare($count_sql, $params);
        $total = (int) $wpdb->get_var($count_query);

        $offset = ($page - 1) * $per_page;

        [$order_by, $order] = self::map_sort($sort);

        $select_sql = sprintf(
            'SELECT * FROM %s%s ORDER BY %s %s LIMIT %%d OFFSET %%d',
            self::table_name(),
            $where_clause,
            $order_by,
            $order
        );

        $select_params = array_merge($params, [$per_page, $offset]);
        $select_query = $wpdb->prepare($select_sql, $select_params);

        $rows = $wpdb->get_results($select_query, ARRAY_A);
        $items = is_array($rows) ? array_map([self::class, 'normalize_row'], $rows) : [];

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => $total > 0 ? (int) ceil($total / $per_page) : 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $download_id): ?array {
        global $wpdb;

        $download_id = absint($download_id);

        if ($download_id <= 0) {
            return null;
        }

        $sql = $wpdb->prepare(
            'SELECT * FROM ' . self::table_name() . ' WHERE id = %d',
            $download_id
        );

        $row = $wpdb->get_row($sql, ARRAY_A);

        return is_array($row) ? self::normalize_row($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function save(array $data): int|false {
        global $wpdb;

        $normalized = self::sanitize_row_input($data);
        $now = current_time('mysql');

        if ($normalized['id'] > 0) {
            $result = $wpdb->update(
                self::table_name(),
                [
                    'title' => $normalized['title'],
                    'slug' => $normalized['slug'],
                    'description' => $normalized['description'],
                    'excerpt' => $normalized['excerpt'],
                    'featured_image_id' => $normalized['featured_image_id'],
                    'file_source' => $normalized['file_source'],
                    'file_id' => $normalized['file_id'],
                    'direct_url' => $normalized['direct_url'],
                    'categories' => $normalized['categories'],
                    'tags' => $normalized['tags'],
                    'status' => $normalized['status'],
                    'updated_at' => $now,
                ],
                ['id' => $normalized['id']],
                ['%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s'],
                ['%d']
            );

            return $result === false ? false : $normalized['id'];
        }

        $result = $wpdb->insert(
            self::table_name(),
            [
                'title' => $normalized['title'],
                'slug' => $normalized['slug'],
                'description' => $normalized['description'],
                'excerpt' => $normalized['excerpt'],
                'featured_image_id' => $normalized['featured_image_id'],
                'file_source' => $normalized['file_source'],
                'file_id' => $normalized['file_id'],
                'direct_url' => $normalized['direct_url'],
                'categories' => $normalized['categories'],
                'tags' => $normalized['tags'],
                'download_count' => 0,
                'status' => $normalized['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        return $result ? (int) $wpdb->insert_id : false;
    }

    public static function delete(int $download_id): bool {
        global $wpdb;

        $download_id = absint($download_id);

        if ($download_id <= 0) {
            return false;
        }

        $deleted = $wpdb->delete(self::table_name(), ['id' => $download_id], ['%d']);

        return $deleted !== false;
    }

    public static function increment_download_count(int $download_id): bool {
        global $wpdb;

        $download_id = absint($download_id);

        if ($download_id <= 0) {
            return false;
        }

        $sql = $wpdb->prepare(
            'UPDATE ' . self::table_name() . ' SET download_count = download_count + 1, updated_at = %s WHERE id = %d',
            current_time('mysql'),
            $download_id
        );

        $result = $wpdb->query($sql);

        return $result === 1;
    }

    public static function get_download_count(int $download_id): int {
        global $wpdb;

        $download_id = absint($download_id);

        if ($download_id <= 0) {
            return 0;
        }

        $sql = $wpdb->prepare(
            'SELECT download_count FROM ' . self::table_name() . ' WHERE id = %d',
            $download_id
        );

        return (int) $wpdb->get_var($sql);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function aggregate_terms(string $column, string $status = 'publish'): array {
        global $wpdb;

        if (! in_array($column, ['categories', 'tags'], true)) {
            return [];
        }

        $status = $status !== '' ? sanitize_key($status) : '';
        $where_sql = '';

        if ($status !== '') {
            $where_sql = $wpdb->prepare(' WHERE status = %s', $status);
        }

        $rows = $wpdb->get_col('SELECT ' . $column . ' FROM ' . self::table_name() . $where_sql);

        if (! is_array($rows)) {
            return [];
        }

        $counts = [];

        foreach ($rows as $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }

            foreach (self::terms_from_csv($value) as $slug) {
                if (! isset($counts[$slug])) {
                    $counts[$slug] = 0;
                }

                $counts[$slug]++;
            }
        }

        foreach (self::option_terms($column) as $slug) {
            if (! isset($counts[$slug])) {
                $counts[$slug] = 0;
            }
        }

        ksort($counts);

        $terms = [];

        foreach ($counts as $slug => $count) {
            $terms[] = [
                'id' => absint(crc32($slug)),
                'name' => self::display_name_from_slug($slug),
                'slug' => $slug,
                'count' => (int) $count,
            ];
        }

        return $terms;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function latest_published(): ?array {
        global $wpdb;

        $sql = "SELECT * FROM " . self::table_name() . " WHERE status = 'publish' ORDER BY created_at DESC, id DESC LIMIT 1";
        $row = $wpdb->get_row($sql, ARRAY_A);

        return is_array($row) ? self::normalize_row($row) : null;
    }

    /**
     * @return array<int, array{id: int, title: string, slug: string, status: string}>
     */
    public static function search_by_title(string $search = '', int $limit = 12, string $status = 'publish'): array {
        global $wpdb;

        $search = sanitize_text_field($search);
        $status = $status !== '' ? sanitize_key($status) : '';
        $limit = max(1, min(50, absint($limit)));

        $where = [];
        $params = [];

        if ($status !== '') {
            $where[] = 'status = %s';
            $params[] = $status;
        }

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = '(title LIKE %s OR slug LIKE %s)';
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $sql = 'SELECT id, title, slug, status FROM ' . self::table_name() . $where_sql . ' ORDER BY created_at DESC, id DESC LIMIT %d';
        $params[] = $limit;
        $query = $wpdb->prepare($sql, $params);
        $rows = $wpdb->get_results($query, ARRAY_A);

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_map(
            static fn(array $row): array => [
                'id' => isset($row['id']) ? (int) $row['id'] : 0,
                'title' => isset($row['title']) ? (string) $row['title'] : '',
                'slug' => isset($row['slug']) ? (string) $row['slug'] : '',
                'status' => isset($row['status']) ? (string) $row['status'] : 'publish',
            ],
            $rows
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function suggest_terms(string $column, string $search = '', int $limit = 10): array {
        $search = sanitize_text_field($search);
        $limit = max(1, min(50, absint($limit)));
        $terms = self::aggregate_terms($column, '');

        if ($search !== '') {
            $needle = sanitize_title($search);
            $terms = array_values(array_filter(
                $terms,
                static function (array $term) use ($needle): bool {
                    $name = isset($term['name']) ? sanitize_title((string) $term['name']) : '';
                    $slug = isset($term['slug']) ? sanitize_title((string) $term['slug']) : '';

                    if ($needle === '') {
                        return true;
                    }

                    return str_contains($slug, $needle) || str_contains($name, $needle);
                }
            ));
        }

        return array_slice($terms, 0, $limit);
    }

    public static function register_term(string $column, string $term): bool {
        if (! in_array($column, ['categories', 'tags'], true)) {
            return false;
        }

        $slug = sanitize_title($term);

        if ($slug === '') {
            return false;
        }

        $terms = self::option_terms($column);

        if (in_array($slug, $terms, true)) {
            return true;
        }

        $terms[] = $slug;
        $terms = array_values(array_unique($terms));
        sort($terms);

        return update_option(self::option_key($column), $terms, false);
    }

    public static function rename_term(string $column, string $old_slug, string $new_slug): bool {
        if (! in_array($column, ['categories', 'tags'], true)) {
            return false;
        }

        $old_slug = sanitize_title($old_slug);
        $new_slug = sanitize_title($new_slug);

        if ($old_slug === '' || $new_slug === '') {
            return false;
        }

        if ($old_slug === $new_slug) {
            return true;
        }

        self::register_term($column, $new_slug);
        self::unregister_term($column, $old_slug);
        self::replace_term_in_rows($column, $old_slug, $new_slug);

        return true;
    }

    public static function remove_term(string $column, string $slug): bool {
        if (! in_array($column, ['categories', 'tags'], true)) {
            return false;
        }

        $slug = sanitize_title($slug);

        if ($slug === '') {
            return false;
        }

        self::unregister_term($column, $slug);
        self::remove_term_from_rows($column, $slug);

        return true;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function sanitize_row_input(array $data): array {
        $title = isset($data['title']) ? sanitize_text_field((string) $data['title']) : '';
        $slug = isset($data['slug']) ? sanitize_title((string) $data['slug']) : sanitize_title($title);

        $source = isset($data['file_source']) ? sanitize_key((string) $data['file_source']) : 'media';
        $source = in_array($source, ['media', 'direct'], true) ? $source : 'media';

        $file_id = isset($data['file_id']) ? absint($data['file_id']) : 0;
        $direct_url = isset($data['direct_url']) ? esc_url_raw((string) $data['direct_url']) : '';

        if ($source === 'media') {
            $direct_url = '';
        } else {
            $file_id = 0;
        }

        return [
            'id' => isset($data['id']) ? absint($data['id']) : 0,
            'title' => $title,
            'slug' => $slug,
            'description' => isset($data['description']) ? wp_kses_post((string) $data['description']) : '',
            'excerpt' => isset($data['excerpt']) ? sanitize_textarea_field((string) $data['excerpt']) : '',
            'featured_image_id' => isset($data['featured_image_id']) ? absint($data['featured_image_id']) : 0,
            'file_source' => $source,
            'file_id' => $file_id,
            'direct_url' => $direct_url,
            'categories' => self::sanitize_terms_value($data['categories'] ?? ''),
            'tags' => self::sanitize_terms_value($data['tags'] ?? ''),
            'status' => isset($data['status']) && sanitize_key((string) $data['status']) === 'draft' ? 'draft' : 'publish',
        ];
    }

    /**
     * @param string|array<int, string> $value
     */
    private static function sanitize_terms_value($value): string {
        $items = is_array($value) ? $value : explode(',', (string) $value);
        $sanitized = [];

        foreach ($items as $item) {
            $slug = sanitize_title((string) $item);

            if ($slug === '') {
                continue;
            }

            $sanitized[] = $slug;
        }

        $sanitized = array_values(array_unique($sanitized));

        return implode(',', $sanitized);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function normalize_row(array $row): array {
        return [
            'id' => isset($row['id']) ? (int) $row['id'] : 0,
            'title' => isset($row['title']) ? (string) $row['title'] : '',
            'slug' => isset($row['slug']) ? (string) $row['slug'] : '',
            'description' => isset($row['description']) ? (string) $row['description'] : '',
            'excerpt' => isset($row['excerpt']) ? (string) $row['excerpt'] : '',
            'featured_image_id' => isset($row['featured_image_id']) ? (int) $row['featured_image_id'] : 0,
            'file_source' => isset($row['file_source']) ? (string) $row['file_source'] : 'media',
            'file_id' => isset($row['file_id']) ? (int) $row['file_id'] : 0,
            'direct_url' => isset($row['direct_url']) ? (string) $row['direct_url'] : '',
            'categories' => isset($row['categories']) ? (string) $row['categories'] : '',
            'tags' => isset($row['tags']) ? (string) $row['tags'] : '',
            'download_count' => isset($row['download_count']) ? (int) $row['download_count'] : 0,
            'status' => isset($row['status']) ? (string) $row['status'] : 'publish',
            'created_at' => isset($row['created_at']) ? (string) $row['created_at'] : '',
            'updated_at' => isset($row['updated_at']) ? (string) $row['updated_at'] : '',
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function map_sort(string $sort): array {
        switch ($sort) {
            case 'oldest':
                return ['created_at', 'ASC'];
            case 'popular':
                return ['download_count', 'DESC'];
            case 'title_asc':
                return ['title', 'ASC'];
            case 'title_desc':
                return ['title', 'DESC'];
            case 'newest':
            default:
                return ['created_at', 'DESC'];
        }
    }

    /**
     * @return array<int, string>
     */
    public static function terms_from_csv(string $csv): array {
        if ($csv === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $csv));
        $parts = array_map('sanitize_title', $parts);
        $parts = array_filter($parts, static fn(string $value): bool => $value !== '');

        return array_values(array_unique($parts));
    }

    private static function display_name_from_slug(string $slug): string {
        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }

    /**
     * @return array<int, string>
     */
    private static function option_terms(string $column): array {
        $saved = get_option(self::option_key($column), []);

        if (! is_array($saved)) {
            return [];
        }

        $terms = array_map(
            static fn($item): string => sanitize_title((string) $item),
            $saved
        );

        $terms = array_filter($terms, static fn(string $slug): bool => $slug !== '');
        $terms = array_values(array_unique($terms));
        sort($terms);

        return $terms;
    }

    private static function unregister_term(string $column, string $slug): bool {
        $slug = sanitize_title($slug);

        if ($slug === '') {
            return false;
        }

        $terms = array_values(array_filter(
            self::option_terms($column),
            static fn(string $saved): bool => $saved !== $slug
        ));

        return update_option(self::option_key($column), $terms, false);
    }

    private static function option_key(string $column): string {
        return $column === 'tags' ? 'gtdm_terms_tags' : 'gtdm_terms_categories';
    }

    private static function replace_term_in_rows(string $column, string $old_slug, string $new_slug): void {
        global $wpdb;

        $rows = $wpdb->get_results(
            'SELECT id, ' . $column . ' FROM ' . self::table_name() . ' WHERE ' . $column . " <> ''",
            ARRAY_A
        );

        if (! is_array($rows)) {
            return;
        }

        $now = current_time('mysql');

        foreach ($rows as $row) {
            $id = isset($row['id']) ? absint($row['id']) : 0;
            $value = isset($row[$column]) ? (string) $row[$column] : '';

            if ($id <= 0 || $value === '') {
                continue;
            }

            $terms = self::terms_from_csv($value);
            $updated = [];
            $changed = false;

            foreach ($terms as $term) {
                if ($term === $old_slug) {
                    $updated[] = $new_slug;
                    $changed = true;
                    continue;
                }

                $updated[] = $term;
            }

            if (! $changed) {
                continue;
            }

            $updated = array_values(array_unique($updated));
            $csv = implode(',', $updated);

            $wpdb->update(
                self::table_name(),
                [$column => $csv, 'updated_at' => $now],
                ['id' => $id],
                ['%s', '%s'],
                ['%d']
            );
        }
    }

    private static function remove_term_from_rows(string $column, string $target): void {
        global $wpdb;

        $rows = $wpdb->get_results(
            'SELECT id, ' . $column . ' FROM ' . self::table_name() . ' WHERE ' . $column . " <> ''",
            ARRAY_A
        );

        if (! is_array($rows)) {
            return;
        }

        $now = current_time('mysql');

        foreach ($rows as $row) {
            $id = isset($row['id']) ? absint($row['id']) : 0;
            $value = isset($row[$column]) ? (string) $row[$column] : '';

            if ($id <= 0 || $value === '') {
                continue;
            }

            $terms = self::terms_from_csv($value);
            $updated = array_values(array_filter(
                $terms,
                static fn(string $term): bool => $term !== $target
            ));

            if (count($terms) === count($updated)) {
                continue;
            }

            $csv = implode(',', $updated);

            $wpdb->update(
                self::table_name(),
                [$column => $csv, 'updated_at' => $now],
                ['id' => $id],
                ['%s', '%s'],
                ['%d']
            );
        }
    }

    /**
     * Build a portable CSV membership WHERE clause that works in MySQL and SQLite.
     *
     * @return array{0: string, 1: array<int, string>}
     */
    private static function build_csv_term_where(string $column, string $term, \wpdb $wpdb): array {
        if (! in_array($column, ['categories', 'tags'], true)) {
            return ['1=0', []];
        }

        $term = sanitize_title($term);
        $term_like = $wpdb->esc_like($term);

        return [
            sprintf(
                '(%1$s = %%s OR %1$s LIKE %%s OR %1$s LIKE %%s OR %1$s LIKE %%s)',
                $column
            ),
            [
                $term,
                $term_like . ',%',
                '%,' . $term_like,
                '%,' . $term_like . ',%',
            ],
        ];
    }
}
