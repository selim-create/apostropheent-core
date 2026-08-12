<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Rest {
    public const NS = 'apostrophe/v1';

    public static function boot(): void {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void {
        register_rest_route(self::NS, '/health', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'health'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NS, '/work', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'work'],
            'permission_callback' => '__return_true',
            'args' => ['lang' => ['default' => 'en', 'sanitize_callback' => 'sanitize_key']],
        ]);

        register_rest_route(self::NS, '/work/(?P<slug>[a-z0-9-]+)', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'work_item'],
            'permission_callback' => '__return_true',
            'args' => [
                'slug' => ['sanitize_callback' => 'sanitize_title'],
                'lang' => ['default' => 'en', 'sanitize_callback' => 'sanitize_key'],
            ],
        ]);

        register_rest_route(self::NS, '/testimonials', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'testimonials'],
            'permission_callback' => '__return_true',
            'args' => ['lang' => ['default' => 'en', 'sanitize_callback' => 'sanitize_key']],
        ]);
    }

    public static function health(): \WP_REST_Response {
        return rest_ensure_response([
            'ok' => true,
            'version' => APOSTROPHE_CORE_VERSION,
            'polylang' => function_exists('pll_get_post_language'),
            'rank_math' => defined('RANK_MATH_VERSION'),
        ]);
    }

    private static function query(string $post_type, string $lang): array {
        $args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
            'no_found_rows' => true,
        ];
        if (function_exists('pll_get_post_language')) { $args['lang'] = $lang; }
        return get_posts($args);
    }

    public static function work(\WP_REST_Request $request): \WP_REST_Response {
        $lang = (string) $request->get_param('lang');
        $items = array_map([self::class, 'work_payload'], self::query(Content_Types::WORK, $lang));
        return rest_ensure_response(['language' => $lang, 'items' => $items]);
    }

    public static function work_item(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        $slug = (string) $request->get_param('slug');
        $lang = (string) $request->get_param('lang');
        $posts = get_posts([
            'post_type' => Content_Types::WORK,
            'name' => $slug,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'lang' => function_exists('pll_get_post_language') ? $lang : '',
        ]);
        if (!$posts) { return new \WP_Error('apostrophe_not_found', 'Work item not found.', ['status' => 404]); }
        return rest_ensure_response(self::work_payload($posts[0]));
    }

    private static function work_payload(\WP_Post $post): array {
        $id = (int) $post->ID;
        $hero = (int) get_post_meta($id, 'ae_hero_media_id', true);
        return [
            'id' => $id,
            'slug' => $post->post_name,
            'language' => current_language_for_post($id),
            'title' => get_the_title($id),
            'service' => (string) get_post_meta($id, 'ae_service_label', true),
            'year' => (int) get_post_meta($id, 'ae_year', true) ?: null,
            'accent' => (string) get_post_meta($id, 'ae_accent', true) ?: 'cream',
            'summary' => get_the_excerpt($id),
            'content' => apply_filters('the_content', $post->post_content),
            'order' => (int) $post->menu_order,
            'thumbnail' => attachment_payload((int) get_post_thumbnail_id($id)),
            'hero_media' => attachment_payload($hero),
            'gallery' => gallery_payload((string) get_post_meta($id, 'ae_gallery_ids', true)),
            'video_url' => esc_url_raw((string) get_post_meta($id, 'ae_video_url', true)),
            'external_link' => [
                'label' => (string) get_post_meta($id, 'ae_external_label', true),
                'url' => esc_url_raw((string) get_post_meta($id, 'ae_external_url', true)),
            ],
            'translations' => self::translations($id),
            'rank_math' => self::rank_math($id),
        ];
    }

    public static function testimonials(\WP_REST_Request $request): \WP_REST_Response {
        $lang = (string) $request->get_param('lang');
        $items = [];
        foreach (self::query(Content_Types::TESTIMONIAL, $lang) as $post) {
            $id = (int) $post->ID;
            $items[] = [
                'id' => $id,
                'language' => current_language_for_post($id),
                'quote' => wp_strip_all_tags($post->post_content),
                'name' => (string) get_post_meta($id, 'ae_person_name', true),
                'role' => (string) get_post_meta($id, 'ae_person_role', true),
                'company' => (string) get_post_meta($id, 'ae_company', true),
                'accent' => (string) get_post_meta($id, 'ae_accent', true) ?: 'cream',
                'order' => (int) $post->menu_order,
                'translations' => self::translations($id),
            ];
        }
        return rest_ensure_response(['language' => $lang, 'items' => $items]);
    }

    private static function translations(int $post_id): array {
        if (!function_exists('pll_get_post_translations')) { return []; }
        $translations = pll_get_post_translations($post_id);
        $result = [];
        foreach ($translations as $lang => $id) {
            $post = get_post((int) $id);
            if ($post) { $result[$lang] = ['id' => (int) $id, 'slug' => $post->post_name]; }
        }
        return $result;
    }

    private static function rank_math(int $post_id): array {
        return [
            'title' => (string) get_post_meta($post_id, 'rank_math_title', true),
            'description' => (string) get_post_meta($post_id, 'rank_math_description', true),
            'focus_keyword' => (string) get_post_meta($post_id, 'rank_math_focus_keyword', true),
        ];
    }
}
