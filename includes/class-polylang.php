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

    public static function resolve_slug(string $language): string {
        $requested = strtolower(trim($language));
        if (!function_exists('pll_languages_list')) { return $requested; }

        $slugs = pll_languages_list(['fields' => 'slug']);
        if (!is_array($slugs) || !$slugs) { return $requested; }

        foreach ($slugs as $slug) {
            if (is_string($slug) && strtolower($slug) === $requested) { return $slug; }
        }

        $locales = pll_languages_list(['fields' => 'locale']);
        if (is_array($locales)) {
            foreach ($locales as $index => $locale) {
                if (!is_string($locale) || !isset($slugs[$index]) || !is_string($slugs[$index])) { continue; }
                $normalized = strtolower(str_replace('-', '_', $locale));
                if ($normalized === $requested || str_starts_with($normalized, $requested . '_')) {
                    return $slugs[$index];
                }
            }
        }

        return $requested;
    }

    public static function logical_language(string $slug): string {
        $slug = strtolower(trim($slug));
        foreach (['en', 'fr'] as $logical) {
            if (self::resolve_slug($logical) === $slug) { return $logical; }
        }
        return $slug;
    }

    public static function language_debug(): array {
        if (!function_exists('pll_languages_list')) { return []; }
        $slugs = pll_languages_list(['fields' => 'slug']);
        $locales = pll_languages_list(['fields' => 'locale']);
        $result = [];
        if (!is_array($slugs)) { return $result; }
        foreach ($slugs as $index => $slug) {
            $result[] = [
                'slug' => is_string($slug) ? $slug : '',
                'locale' => (is_array($locales) && isset($locales[$index]) && is_string($locales[$index])) ? $locales[$index] : '',
            ];
        }
        return $result;
    }

    public static function translated_post_id(int $post_id, string $lang): int {
        if (!function_exists('pll_get_post')) { return $post_id; }
        $translated = pll_get_post($post_id, self::resolve_slug($lang));
        return is_numeric($translated) ? (int) $translated : $post_id;
    }
}
