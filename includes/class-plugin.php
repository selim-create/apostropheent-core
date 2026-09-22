<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Plugin {
    private static ?self $instance = null;
    private const DEFAULT_FRONTEND_URL = 'https://apostropheent.vercel.app';
    private const PREVIEW_TTL = 3600;

    private function __construct() {}

    public static function instance(): self {
        if (null === self::$instance) { self::$instance = new self(); }
        return self::$instance;
    }

    public function boot(): void {
        Content_Types::boot();
        Meta_Boxes::boot();
        Settings::boot();
        Rest::boot();
        Polylang::boot();
        Revalidation::boot();
        Security::boot();
        Importer::boot();
        Rank_Math::boot();

        add_action('template_redirect', [self::class, 'redirect_api_root'], 0);
        add_filter('post_type_link', [self::class, 'frontend_permalink'], 10, 2);
        add_filter('preview_post_link', [self::class, 'frontend_preview_link'], 10, 2);
    }

    public static function frontend_url(): string {
        $configured = trim((string) get_option('apostrophe_core_frontend_url', ''));
        $url = $configured !== '' ? $configured : self::DEFAULT_FRONTEND_URL;
        return untrailingslashit(esc_url_raw($url));
    }

    public static function frontend_permalink(string $permalink, \WP_Post $post): string {
        if (!in_array($post->post_type, [
            Content_Types::HOME,
            Content_Types::SERVICE,
            Content_Types::FIELD,
            Content_Types::WORK,
            Content_Types::TESTIMONIAL,
        ], true)) {
            return $permalink;
        }

        return self::post_url($post);
    }

    public static function post_url(\WP_Post $post): string {
        $lang = current_language_for_post((int) $post->ID);
        $is_fr = 'fr' === $lang;
        $base = self::frontend_url();
        $slug = $post->post_name !== '' ? $post->post_name : sanitize_title($post->post_title);

        switch ($post->post_type) {
            case Content_Types::HOME:
                return $base . ($is_fr ? '/fr' : '');
            case Content_Types::SERVICE:
                return $base . ($is_fr ? '/fr#services' : '/#services');
            case Content_Types::FIELD:
                return $base . ($is_fr ? '/fr#fields' : '/#fields');
            case Content_Types::WORK:
                return $base . ($is_fr ? '/fr/projets/' : '/work/') . rawurlencode($slug);
            case Content_Types::TESTIMONIAL:
                return $base . ($is_fr ? '/fr/temoignages' : '/testimonials') . '#testimonial-' . (int) $post->ID;
            default:
                return $base;
        }
    }

    public static function frontend_preview_link(string $preview_link, \WP_Post $post): string {
        if (!in_array($post->post_type, [Content_Types::WORK, Content_Types::TESTIMONIAL], true)) {
            return $preview_link;
        }

        $expires = time() + self::PREVIEW_TTL;
        $token = self::preview_token((int) $post->ID, $expires);
        $url = self::post_url($post);
        $fragment = '';

        if (str_contains($url, '#')) {
            [$url, $anchor] = explode('#', $url, 2);
            $fragment = '#' . $anchor;
        }

        return add_query_arg([
            'ae_preview' => '1',
            'post_id' => (int) $post->ID,
            'expires' => $expires,
            'token' => $token,
        ], $url) . $fragment;
    }

    public static function preview_token(int $post_id, int $expires): string {
        return hash_hmac('sha256', $post_id . '|' . $expires, wp_salt('auth'));
    }

    public static function verify_preview_token(int $post_id, int $expires, string $token): bool {
        if ($post_id <= 0 || $expires < time() || $token === '') { return false; }
        return hash_equals(self::preview_token($post_id, $expires), $token);
    }

    public static function redirect_api_root(): void {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) { return; }
        if (defined('REST_REQUEST') && REST_REQUEST) { return; }
        if (isset($_GET['rest_route'])) { return; }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/';
        $path = (string) parse_url($request_uri, PHP_URL_PATH);
        if ('/' !== $path) { return; }

        nocache_headers();
        wp_redirect(self::frontend_url(), 302, 'ApostropheEnt Headless');
        exit;
    }

    public static function activate(): void {
        Content_Types::register();
        update_option('apostrophe_core_schema_version', APOSTROPHE_CORE_SCHEMA_VERSION, false);
        flush_rewrite_rules(false);
    }

    public static function deactivate(): void {
        flush_rewrite_rules(false);
    }
}
