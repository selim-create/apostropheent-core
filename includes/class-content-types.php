<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Content_Types {
    public const WORK = 'ae_work';
    public const TESTIMONIAL = 'ae_testimonial';

    public static function boot(): void {
        add_action('init', [self::class, 'register']);
    }

    public static function register(): void {
        register_post_type(self::WORK, [
            'labels' => [
                'name' => 'Work',
                'singular_name' => 'Work',
                'add_new_item' => 'Add New Work',
                'edit_item' => 'Edit Work',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-portfolio',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'],
            'rewrite' => false,
            'has_archive' => false,
        ]);

        register_post_type(self::TESTIMONIAL, [
            'labels' => [
                'name' => 'Testimonials',
                'singular_name' => 'Testimonial',
                'add_new_item' => 'Add New Testimonial',
                'edit_item' => 'Edit Testimonial',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-format-quote',
            'supports' => ['title', 'editor', 'page-attributes'],
            'rewrite' => false,
            'has_archive' => false,
        ]);
    }
}
