<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Headless {
    private const DEFAULT_FRONTEND_URL = 'https://apostropheent.vercel.app';
    private const SLUG_NONCE = 'apostrophe_core_slug_nonce';
    private const SLUG_ACTION = 'apostrophe_core_save_slug';
    private const PREVIEW_TTL = 3600;

    private static bool $saving_slug = false;

    public static function boot(): void {
        add_action('template_redirect', [self::class, 'redirect_public_requests'], 0);
        add_filter('post_type_link', [self::class, 'frontend_permalink'], 10, 2);
        add_filter('preview_post_link', [self::class, 'preview_link'], 10, 2);
        add_action('add_meta_boxes', [self::class, 'register_slug_box']);
        add_action('save_post', [self::class, 'save_slug'], 20, 2);
    }

    private static function supported_types(): array {
        return [
            Content_Types::HOME,
            Content_Types::SERVICE,
            Content_Types::FIELD,
            Content_Types::WORK,
            Content_Types::TESTIMONIAL,
        ];
    }

    private static function slug_types(): array {
        return [
            Content_Types::SERVICE,
            Content_Types::FIELD,
            Content_Types::WORK,
            Content_Types::TESTIMONIAL,
        ];
    }

    public static function frontend_url(): string {
        $configured = trim((string) get_option('apostrophe_core_frontend_url', ''));
        $url = $configured !== '' ? $configured : self::DEFAULT_FRONTEND_URL;
        return untrailingslashit(esc_url_raw($url));
    }

    public static function frontend_permalink(string $permalink, \WP_Post $post): string {
        if (!in_array($post->post_type, self::supported_types(), true)) { return $permalink; }
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

    public static function preview_link(string $preview_link, \WP_Post $post): string {
        if (!in_array($post->post_type, [Content_Types::WORK, Content_Types::TESTIMONIAL], true)) {
            return self::frontend_permalink($preview_link, $post);
        }

        $expires = time() + self::PREVIEW_TTL;
        $token = self::preview_token((int) $post->ID, $expires);
        $url = self::post_url($post);
        $anchor = '';

        if (str_contains($url, '#')) {
            [$url, $fragment] = explode('#', $url, 2);
            $anchor = '#' . $fragment;
        }

        return add_query_arg([
            'ae_preview' => '1',
            'post_id' => (int) $post->ID,
            'expires' => $expires,
            'token' => $token,
        ], $url) . $anchor;
    }

    public static function preview_token(int $post_id, int $expires): string {
        return hash_hmac('sha256', $post_id . '|' . $expires, wp_salt('auth'));
    }

    public static function verify_preview_token(int $post_id, int $expires, string $token): bool {
        if ($post_id <= 0 || $expires < time() || $token === '') { return false; }
        return hash_equals(self::preview_token($post_id, $expires), $token);
    }

    public static function register_slug_box(): void {
        foreach (self::slug_types() as $post_type) {
            add_meta_box(
                'ae-frontend-slug',
                'Frontend URL',
                [self::class, 'slug_box'],
                $post_type,
                'side',
                'high'
            );
        }
    }

    public static function slug_box(\WP_Post $post): void {
        wp_nonce_field(self::SLUG_ACTION, self::SLUG_NONCE);
        $slug = $post->post_name !== '' ? $post->post_name : sanitize_title($post->post_title);

        echo '<p><label for="ae_frontend_slug"><strong>Kısa URL (slug)</strong></label></p>';
        echo '<input type="text" id="ae_frontend_slug" name="ae_frontend_slug" class="widefat" value="' . esc_attr($slug) . '" placeholder="ornek-proje">';
        echo '<p class="description">Yalnızca URL’nin son bölümünü yazın. Boşluklar ve özel karakterler otomatik temizlenir.</p>';

        if ($post->post_status !== 'auto-draft' && $slug !== '') {
            echo '<p><a href="' . esc_url(self::post_url($post)) . '" target="_blank" rel="noopener noreferrer">Frontend’de görüntüle ↗</a></p>';
        }
    }

    public static function save_slug(int $post_id, \WP_Post $post): void {
        if (self::$saving_slug || !in_array($post->post_type, self::slug_types(), true)) { return; }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        if (!current_user_can('edit_post', $post_id)) { return; }

        $nonce = isset($_POST[self::SLUG_NONCE]) ? sanitize_text_field(wp_unslash($_POST[self::SLUG_NONCE])) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, self::SLUG_ACTION) || !isset($_POST['ae_frontend_slug'])) { return; }

        $slug = sanitize_title((string) wp_unslash($_POST['ae_frontend_slug']));
        if ($slug === '' || $slug === $post->post_name) { return; }

        $slug = wp_unique_post_slug($slug, $post_id, $post->post_status, $post->post_type, (int) $post->post_parent);

        self::$saving_slug = true;
        wp_update_post([
            'ID' => $post_id,
            'post_name' => $slug,
        ]);
        self::$saving_slug = false;
    }

    public static function redirect_public_requests(): void {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) { return; }
        if (defined('REST_REQUEST') && REST_REQUEST) { return; }
        if (isset($_GET['rest_route'])) { return; }

        nocache_headers();
        wp_redirect(self::frontend_url(), 302, 'ApostropheEnt Headless');
        exit;
    }
}
