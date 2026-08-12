<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Revalidation {
    private static bool $fired = false;

    public static function boot(): void {
        add_action('save_post', [self::class, 'post_saved'], 30, 3);
        add_action('updated_option', [self::class, 'option_updated'], 20, 3);
    }

    public static function post_saved(int $post_id, \WP_Post $post, bool $update): void {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) { return; }
        if (!in_array($post->post_type, [Content_Types::HOME, Content_Types::SERVICE, Content_Types::FIELD, Content_Types::WORK, Content_Types::TESTIMONIAL], true)) { return; }
        self::trigger(['reason' => $update ? 'content_updated' : 'content_created', 'post_id' => $post_id, 'post_type' => $post->post_type]);
    }

    public static function option_updated(string $option, mixed $old_value, mixed $value): void {
        if (!str_starts_with($option, 'apostrophe_core_')) { return; }
        if (in_array($option, ['apostrophe_core_revalidate_url', 'apostrophe_core_revalidate_secret'], true)) { return; }
        self::trigger(['reason' => 'settings_updated', 'option' => $option]);
    }

    private static function trigger(array $context): void {
        if (self::$fired) { return; }
        $url = esc_url_raw((string) get_option('apostrophe_core_revalidate_url', ''));
        if ($url === '') { return; }
        self::$fired = true;
        wp_safe_remote_post($url, [
            'timeout' => 3,
            'blocking' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Apostrophe-Revalidate-Secret' => (string) get_option('apostrophe_core_revalidate_secret', ''),
            ],
            'body' => wp_json_encode(array_merge(['timestamp' => gmdate('c')], $context)),
        ]);
    }
}
