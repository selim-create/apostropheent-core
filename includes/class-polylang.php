<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Polylang {
    public static function boot(): void {
        add_filter('pll_get_post_types', [self::class, 'post_types'], 10, 2);
    }

    public static function post_types(array $post_types, bool $is_settings): array {
        foreach ([Content_Types::HOME, Content_Types::SERVICE, Content_Types::FIELD, Content_Types::WORK, Content_Types::TESTIMONIAL] as $post_type) {
            $post_types[$post_type] = $post_type;
        }
        return $post_types;
    }

    public static function translated_post_id(int $post_id, string $lang): int {
        if (!function_exists('pll_get_post')) { return $post_id; }
        $translated = pll_get_post($post_id, $lang);
        return is_numeric($translated) ? (int) $translated : $post_id;
    }
}
