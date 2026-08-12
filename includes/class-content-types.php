<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Content_Types {
    public const HOME = 'ae_home';
    public const SERVICE = 'ae_service';
    public const FIELD = 'ae_field';
    public const WORK = 'ae_work';
    public const TESTIMONIAL = 'ae_testimonial';

    public static function boot(): void {
        add_action('init', [self::class, 'register']);
    }

    public static function register(): void {
        self::register_type(self::HOME, 'Home', 'Home', ['title', 'editor', 'thumbnail'], 'dashicons-admin-home', 20);
        self::register_type(self::SERVICE, 'Services', 'Service', ['title', 'editor', 'thumbnail', 'page-attributes'], 'dashicons-megaphone', 21);
        self::register_type(self::FIELD, 'Fields', 'Field', ['title', 'page-attributes'], 'dashicons-screenoptions', 22);
        self::register_type(self::WORK, 'Work', 'Work', ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'], 'dashicons-portfolio', 23);
        self::register_type(self::TESTIMONIAL, 'Testimonials', 'Testimonial', ['title', 'editor', 'page-attributes'], 'dashicons-format-quote', 24);
    }

    private static function register_type(string $post_type, string $plural, string $singular, array $supports, string $icon, int $position): void {
        register_post_type($post_type, [
            'labels' => [
                'name' => $plural,
                'singular_name' => $singular,
                'add_new_item' => 'Add New ' . $singular,
                'edit_item' => 'Edit ' . $singular,
                'new_item' => 'New ' . $singular,
                'view_item' => 'View ' . $singular,
                'search_items' => 'Search ' . $plural,
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'apostrophe-core',
            'show_in_rest' => true,
            'supports' => $supports,
            'hierarchical' => false,
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'menu_icon' => $icon,
            'menu_position' => $position,
        ]);
    }
}
