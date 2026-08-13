<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Rest {
    public const NS = 'apostrophe/v1';

    public static function boot(): void { add_action('rest_api_init', [self::class, 'routes']); }

    public static function routes(): void {
        register_rest_route(self::NS, '/health', ['methods' => \WP_REST_Server::READABLE, 'callback' => [self::class, 'health'], 'permission_callback' => '__return_true']);
        register_rest_route(self::NS, '/site', ['methods' => \WP_REST_Server::READABLE, 'callback' => [self::class, 'site'], 'permission_callback' => '__return_true', 'args' => ['lang' => ['default' => 'en', 'sanitize_callback' => 'sanitize_key']]]);
        register_rest_route(self::NS, '/work', ['methods' => \WP_REST_Server::READABLE, 'callback' => [self::class, 'work'], 'permission_callback' => '__return_true', 'args' => ['lang' => ['default' => 'en', 'sanitize_callback' => 'sanitize_key']]]);
        register_rest_route(self::NS, '/work/(?P<slug>[a-z0-9-]+)', ['methods' => \WP_REST_Server::READABLE, 'callback' => [self::class, 'work_item'], 'permission_callback' => '__return_true', 'args' => ['slug' => ['sanitize_callback' => 'sanitize_title'], 'lang' => ['default' => 'en', 'sanitize_callback' => 'sanitize_key']]]);
        register_rest_route(self::NS, '/testimonials', ['methods' => \WP_REST_Server::READABLE, 'callback' => [self::class, 'testimonials'], 'permission_callback' => '__return_true', 'args' => ['lang' => ['default' => 'en', 'sanitize_callback' => 'sanitize_key']]]);
    }

    private static function text(string $value): string {
        return html_entity_decode(wp_specialchars_decode($value, ENT_QUOTES), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function health(): \WP_REST_Response {
        return rest_ensure_response([
            'ok' => true,
            'version' => APOSTROPHE_CORE_VERSION,
            'schema_version' => APOSTROPHE_CORE_SCHEMA_VERSION,
            'polylang' => function_exists('pll_get_post_language'),
            'rank_math' => defined('RANK_MATH_VERSION'),
            'languages' => Polylang::language_debug(),
            'resolved_languages' => ['en' => Polylang::resolve_slug('en'), 'fr' => Polylang::resolve_slug('fr')],
        ]);
    }

    private static function query(string $post_type, string $lang): array {
        $orderby = Content_Types::WORK === $post_type
            ? ['date' => 'DESC', 'ID' => 'DESC']
            : ['menu_order' => 'ASC', 'date' => 'ASC'];

        $args = ['post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => $orderby, 'no_found_rows' => true, 'suppress_filters' => false];
        if (function_exists('pll_get_post_language')) { $args['lang'] = Polylang::resolve_slug($lang); }
        return get_posts($args);
    }

    private static function query_with_fallback(string $post_type, string $lang): array {
        $items = self::query($post_type, $lang);
        if (!$items && 'en' !== $lang) { $items = self::query($post_type, 'en'); }
        return $items;
    }

    private static function first(string $post_type, string $lang): ?\WP_Post { $posts = self::query($post_type, $lang); return $posts[0] ?? null; }

    public static function site(\WP_REST_Request $request): \WP_REST_Response {
        $lang = (string) $request->get_param('lang');
        $home = self::first(Content_Types::HOME, $lang);
        $services = array_map(static fn(\WP_Post $post): array => [
            'id' => (int) $post->ID,
            'slug' => $post->post_name,
            'title' => self::text(get_the_title($post)),
            'content' => apply_filters('the_content', $post->post_content),
            'image' => attachment_payload((int) get_post_thumbnail_id($post->ID)),
            'style_key' => self::text((string) get_post_meta($post->ID, 'ae_style_key', true)),
            'order' => (int) $post->menu_order,
        ], self::query(Content_Types::SERVICE, $lang));
        $fields = array_map(static fn(\WP_Post $post): array => [
            'id' => (int) $post->ID,
            'slug' => $post->post_name,
            'title' => self::text(get_the_title($post)),
            'order' => (int) $post->menu_order,
        ], self::query(Content_Types::FIELD, $lang));

        $logical_lang = 'fr' === $lang ? 'fr' : 'en';

        return rest_ensure_response([
            'schema_version' => APOSTROPHE_CORE_SCHEMA_VERSION,
            'language' => $lang,
            'resolved_language' => Polylang::resolve_slug($lang),
            'home' => $home ? [
                'id' => (int) $home->ID,
                'title' => self::text(get_the_title($home)),
                'hero_title' => self::text((string) get_post_meta($home->ID, 'ae_hero_title', true)),
                'about_heading' => self::text((string) get_post_meta($home->ID, 'ae_about_heading', true)),
                'about_content' => apply_filters('the_content', $home->post_content),
                'services_heading' => self::text((string) get_post_meta($home->ID, 'ae_services_heading', true)),
                'fields_heading' => self::text((string) get_post_meta($home->ID, 'ae_fields_heading', true)),
                'contact_heading' => self::text((string) get_post_meta($home->ID, 'ae_contact_heading', true)),
                'hero_desktop' => attachment_payload((int) get_post_meta($home->ID, 'ae_hero_desktop_id', true)),
                'hero_mobile' => attachment_payload((int) get_post_meta($home->ID, 'ae_hero_mobile_id', true)),
                'translations' => self::translations((int) $home->ID),
                'rank_math' => self::rank_math((int) $home->ID),
            ] : null,
            'services' => $services,
            'fields' => $fields,
            'listing_seo' => [
                'work' => [
                    'title' => self::text((string) get_option('apostrophe_core_work_seo_title_' . $logical_lang, '')),
                    'description' => self::text((string) get_option('apostrophe_core_work_seo_description_' . $logical_lang, '')),
                ],
                'testimonials' => [
                    'title' => self::text((string) get_option('apostrophe_core_testimonials_seo_title_' . $logical_lang, '')),
                    'description' => self::text((string) get_option('apostrophe_core_testimonials_seo_description_' . $logical_lang, '')),
                ],
            ],
            'contact' => [
                'email' => sanitize_email((string) get_option('apostrophe_core_site_email', '')),
                'phone' => self::text(sanitize_text_field((string) get_option('apostrophe_core_site_phone', ''))),
                'instagram' => esc_url_raw((string) get_option('apostrophe_core_instagram_url', '')),
                'linkedin' => esc_url_raw((string) get_option('apostrophe_core_linkedin_url', '')),
                'addresses' => [
                    'london' => self::text((string) get_option('apostrophe_core_london_address', '')),
                    'paris' => self::text((string) get_option('apostrophe_core_paris_address', '')),
                    'istanbul' => self::text((string) get_option('apostrophe_core_istanbul_address', '')),
                ],
            ],
        ]);
    }

    public static function work(\WP_REST_Request $request): \WP_REST_Response {
        $lang = (string) $request->get_param('lang');
        $posts = self::query_with_fallback(Content_Types::WORK, $lang);
        return rest_ensure_response(['language' => $lang, 'fallback_language' => ($posts && current_language_for_post((int) $posts[0]->ID) !== $lang) ? 'en' : null, 'items' => array_map([self::class, 'work_payload'], $posts)]);
    }

    public static function work_item(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        $slug = (string) $request->get_param('slug'); $lang = (string) $request->get_param('lang'); $posts = self::find_work($slug, $lang); $fallback = false;
        if (!$posts && 'en' !== $lang) { $posts = self::find_work($slug, 'en'); $fallback = (bool) $posts; }
        if (!$posts) { return new \WP_Error('apostrophe_not_found', 'Work item not found.', ['status' => 404]); }
        $payload = self::work_payload($posts[0]); $payload['requested_language'] = $lang; $payload['fallback_language'] = $fallback ? 'en' : null; return rest_ensure_response($payload);
    }

    private static function find_work(string $slug, string $lang): array {
        $args = ['post_type' => Content_Types::WORK, 'name' => $slug, 'post_status' => 'publish', 'posts_per_page' => 1, 'suppress_filters' => false];
        if (function_exists('pll_get_post_language')) { $args['lang'] = Polylang::resolve_slug($lang); }
        return get_posts($args);
    }

    private static function work_payload(\WP_Post $post): array {
        $id = (int) $post->ID;
        return [
            'id' => $id,
            'slug' => $post->post_name,
            'language' => current_language_for_post($id),
            'title' => self::text(get_the_title($id)),
            'service' => self::text((string) get_post_meta($id, 'ae_service_label', true)),
            'year' => (int) get_post_meta($id, 'ae_year', true) ?: null,
            'accent' => (string) get_post_meta($id, 'ae_accent', true) ?: 'cream',
            'summary' => self::text(get_the_excerpt($id)),
            'content' => apply_filters('the_content', $post->post_content),
            'order' => (int) $post->menu_order,
            'thumbnail' => attachment_payload((int) get_post_thumbnail_id($id)),
            'hero_media' => attachment_payload((int) get_post_meta($id, 'ae_hero_media_id', true)),
            'gallery' => gallery_payload((string) get_post_meta($id, 'ae_gallery_ids', true)),
            'video_url' => esc_url_raw((string) get_post_meta($id, 'ae_video_url', true)),
            'videos' => Work_Videos::payload($id),
            'external_link' => [
                'label' => self::text((string) get_post_meta($id, 'ae_external_label', true)),
                'url' => esc_url_raw((string) get_post_meta($id, 'ae_external_url', true)),
            ],
            'translations' => self::translations($id),
            'rank_math' => self::rank_math($id),
        ];
    }

    public static function testimonials(\WP_REST_Request $request): \WP_REST_Response {
        $lang = (string) $request->get_param('lang'); $posts = self::query_with_fallback(Content_Types::TESTIMONIAL, $lang); $items = [];
        foreach ($posts as $post) {
            $id = (int) $post->ID;
            $items[] = [
                'id' => $id,
                'language' => current_language_for_post($id),
                'quote' => self::text(wp_strip_all_tags($post->post_content)),
                'name' => self::text((string) get_post_meta($id, 'ae_person_name', true)),
                'role' => self::text((string) get_post_meta($id, 'ae_person_role', true)),
                'company' => self::text((string) get_post_meta($id, 'ae_company', true)),
                'accent' => (string) get_post_meta($id, 'ae_accent', true) ?: 'cream',
                'order' => (int) $post->menu_order,
                'translations' => self::translations($id),
            ];
        }
        return rest_ensure_response(['language' => $lang, 'fallback_language' => ($posts && current_language_for_post((int) $posts[0]->ID) !== $lang) ? 'en' : null, 'items' => $items]);
    }

    private static function translations(int $post_id): array {
        if (!function_exists('pll_get_post_translations')) { return []; }
        $result = [];
        foreach (pll_get_post_translations($post_id) as $lang => $id) { $post = get_post((int) $id); if ($post) { $result[Polylang::logical_language((string) $lang)] = ['id' => (int) $id, 'slug' => $post->post_name]; } }
        return $result;
    }

    private static function rank_math(int $post_id): array {
        return [
            'title' => self::text((string) get_post_meta($post_id, 'rank_math_title', true)),
            'description' => self::text((string) get_post_meta($post_id, 'rank_math_description', true)),
            'focus_keyword' => self::text((string) get_post_meta($post_id, 'rank_math_focus_keyword', true)),
        ];
    }
}
