<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

/**
 * Rank Math compatibility for the headless Work and Testimonial content models.
 *
 * These CPTs deliberately do not expose native WordPress frontend URLs, so we
 * explicitly opt them into Rank Math's admin integration and force the SEO
 * controls to be available on their edit screens.
 */
final class Rank_Math {
    private const POST_TYPES = [
        Content_Types::WORK,
        Content_Types::TESTIMONIAL,
    ];

    public static function boot(): void {
        // Rank Math documents this filter for opting otherwise excluded custom
        // post types into its Titles & Meta / editor integration.
        add_filter('rank_math/excluded_post_types', [self::class, 'include_post_types'], 11);

        // Ensure the SEO meta box/sidebar is available on our two editor screens
        // even before the per-post-type "Add SEO Controls" option is toggled.
        add_filter('rank_math/metabox/add_seo_metabox', [self::class, 'show_seo_metabox'], 20);
    }

    /**
     * @param mixed $post_types
     * @return mixed
     */
    public static function include_post_types($post_types) {
        if (!is_array($post_types)) {
            return $post_types;
        }

        foreach (self::POST_TYPES as $post_type) {
            $post_types[$post_type] = $post_type;
        }

        return $post_types;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function show_seo_metabox($default) {
        $post_type = self::current_post_type();

        if ($post_type && in_array($post_type, self::POST_TYPES, true)) {
            return true;
        }

        return $default;
    }

    private static function current_post_type(): string {
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && !empty($screen->post_type)) {
                return (string) $screen->post_type;
            }
        }

        if (!empty($GLOBALS['post_type'])) {
            return sanitize_key((string) $GLOBALS['post_type']);
        }

        if (!empty($_GET['post_type'])) {
            return sanitize_key(wp_unslash((string) $_GET['post_type']));
        }

        if (!empty($_GET['post'])) {
            $post_type = get_post_type((int) $_GET['post']);
            if (is_string($post_type)) {
                return $post_type;
            }
        }

        return '';
    }
}
