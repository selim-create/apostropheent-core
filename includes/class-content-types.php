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
        self::register_type(self::HOME, 'Ana Sayfa', 'Ana Sayfa', ['title', 'editor', 'thumbnail']);
        self::register_type(self::SERVICE, 'Hizmetler', 'Hizmet', ['title', 'editor', 'thumbnail', 'page-attributes']);
        self::register_type(self::FIELD, 'Alanlar', 'Alan', ['title', 'page-attributes']);
        self::register_type(self::WORK, 'Projeler', 'Proje', ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes']);
        self::register_type(self::TESTIMONIAL, 'Müşteri Görüşleri', 'Müşteri Görüşü', ['title', 'editor', 'page-attributes']);
    }

    private static function register_type(string $post_type, string $plural, string $singular, array $supports): void {
        register_post_type($post_type, [
            'labels' => [
                'name' => $plural,
                'singular_name' => $singular,
                'menu_name' => $plural,
                'name_admin_bar' => $singular,
                'all_items' => $plural,
                'add_new' => 'Yeni Ekle',
                'add_new_item' => 'Yeni ' . $singular . ' Ekle',
                'edit_item' => $singular . ' Düzenle',
                'new_item' => 'Yeni ' . $singular,
                'view_item' => $singular . ' Görüntüle',
                'view_items' => $plural . ' Görüntüle',
                'search_items' => $plural . ' Ara',
                'not_found' => 'Kayıt bulunamadı.',
                'not_found_in_trash' => 'Çöp kutusunda kayıt bulunamadı.',
                'archives' => $plural . ' Arşivi',
                'attributes' => $singular . ' Özellikleri',
                'insert_into_item' => $singular . ' içine ekle',
                'uploaded_to_this_item' => 'Bu ' . mb_strtolower($singular) . ' için yüklenenler',
                'filter_items_list' => $plural . ' listesini filtrele',
                'items_list_navigation' => $plural . ' liste navigasyonu',
                'items_list' => $plural . ' listesi',
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
        ]);
    }
}
