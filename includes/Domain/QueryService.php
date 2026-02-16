<?php

declare(strict_types=1);

namespace GTDownloadsManager\Domain;

class QueryService {
    /**
     * @return array{state: array<string, mixed>, query_args: array<string, mixed>}
     */
    public static function build_query_args(array $input = [], bool $merge_request = true): array {
        $state = [
            'search' => isset($input['search']) ? sanitize_text_field((string) $input['search']) : '',
            'category' => isset($input['category']) ? sanitize_title((string) $input['category']) : '',
            'tag' => isset($input['tag']) ? sanitize_title((string) $input['tag']) : '',
            'sort' => isset($input['sort']) ? self::sanitize_sort((string) $input['sort']) : 'newest',
            'page' => isset($input['page']) ? max(1, absint($input['page'])) : 1,
            'per_page' => isset($input['per_page']) ? self::sanitize_per_page((int) $input['per_page']) : 12,
            'layout' => isset($input['layout']) ? self::sanitize_layout((string) $input['layout']) : 'grid',
            'filters' => isset($input['filters']) ? (int) (bool) $input['filters'] : 1,
            'image' => isset($input['image']) ? sanitize_key((string) $input['image']) : 'medium',
        ];

        if ($merge_request) {
            $request_state = self::get_request_state();

            foreach ($request_state as $key => $value) {
                if ($value === '') {
                    continue;
                }

                if ($key === 'page' && (int) $value <= 0) {
                    continue;
                }

                if (in_array($key, ['per_page', 'layout', 'filters', 'image'], true)) {
                    continue;
                }

                $state[$key] = $value;
            }
        }

        $query_args = [
            'search' => $state['search'],
            'category' => $state['category'],
            'tag' => $state['tag'],
            'sort' => $state['sort'],
            'page' => $state['page'],
            'per_page' => $state['per_page'],
            'status' => 'publish',
        ];

        return [
            'state' => $state,
            'query_args' => $query_args,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get_request_state(): array {
        $search = self::request_value('gtdm_s');
        $category = self::request_value('gtdm_cat');
        $tag = self::request_value('gtdm_tag');
        $sort = self::request_value('gtdm_sort');
        $page_raw = self::request_value('gtdm_page');
        $page = absint($page_raw);

        return [
            'search' => sanitize_text_field($search),
            'category' => sanitize_title($category),
            'tag' => sanitize_title($tag),
            'sort' => $sort !== '' ? self::sanitize_sort($sort) : '',
            // Keep 0 when missing so shortcode/block defaults are not overridden.
            'page' => $page > 0 ? $page : 0,
            'per_page' => 0,
            'layout' => '',
            'filters' => 1,
            'image' => '',
        ];
    }

    private static function request_value(string $key): string {
        $value = get_query_var($key, '');

        if ($value === '' && isset($_GET[$key])) {
            $value = wp_unslash($_GET[$key]);
        }

        return is_scalar($value) ? (string) $value : '';
    }

    private static function sanitize_layout(string $layout): string {
        $layout = sanitize_key($layout);

        return in_array($layout, ['grid', 'table'], true) ? $layout : 'grid';
    }

    private static function sanitize_per_page(int $per_page): int {
        if ($per_page < 1) {
            return 12;
        }

        return min($per_page, 50);
    }

    public static function sanitize_sort(string $sort): string {
        $sort = sanitize_key($sort);

        $valid = ['newest', 'oldest', 'popular', 'title_asc', 'title_desc'];

        return in_array($sort, $valid, true) ? $sort : 'newest';
    }
}
