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
        add_filter('manage_' . self::WORK . '_posts_columns', [self::class, 'work_columns']);
        add_action('manage_' . self::WORK . '_posts_custom_column', [self::class, 'work_column'], 10, 2);
        add_filter('manage_' . self::TESTIMONIAL . '_posts_columns', [self::class, 'testimonial_columns']);
        add_action('manage_' . self::TESTIMONIAL . '_posts_custom_column', [self::class, 'testimonial_column'], 10, 2);
    }

    public static function register(): void {
        self::register_type(self::HOME, 'Ana Sayfa', 'Ana Sayfa', ['title', 'editor', 'thumbnail']);
        self::register_type(self::SERVICE, 'Hizmetler', 'Hizmet', ['title', 'editor', 'thumbnail', 'page-attributes']);
        self::register_type(self::FIELD, 'Alanlar', 'Alan', ['title', 'page-attributes']);
        self::register_type(self::WORK, 'Projeler', 'Proje', ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'], true);
        self::register_type(self::TESTIMONIAL, 'Müşteri Görüşleri', 'Müşteri Görüşü', ['title', 'editor', 'page-attributes'], true);
    }

    public static function work_columns(array $columns): array {
        $result = [];
        foreach ($columns as $key => $label) {
            $result[$key] = $label;
            if ('title' === $key) {
                $result['ae_language'] = 'Dil';
                $result['ae_presentation'] = 'Sunum';
                $result['ae_publisher'] = 'Yayın / Medya';
                $result['ae_year'] = 'Yıl';
            }
        }
        return $result;
    }

    public static function work_column(string $column, int $post_id): void {
        if ('ae_language' === $column) {
            echo '<span class="ae-chip">' . esc_html(strtoupper(current_language_for_post($post_id))) . '</span>';
            return;
        }

        if ('ae_presentation' === $column) {
            $presentation = (string) get_post_meta($post_id, 'ae_presentation_type', true);
            echo esc_html('media_feature' === $presentation ? 'Media Feature' : 'Standard');
            return;
        }

        if ('ae_publisher' === $column) {
            $publisher = trim((string) get_post_meta($post_id, 'ae_media_publisher', true));
            echo $publisher !== '' ? esc_html($publisher) : '<span class="ae-muted">—</span>';
            return;
        }

        if ('ae_year' === $column) {
            $year = (int) get_post_meta($post_id, 'ae_year', true);
            echo $year > 0 ? esc_html((string) $year) : '<span class="ae-muted">—</span>';
        }
    }

    public static function testimonial_columns(array $columns): array {
        $result = [];
        foreach ($columns as $key => $label) {
            $result[$key] = $label;
            if ('title' === $key) {
                $result['ae_language'] = 'Dil';
                $result['ae_person'] = 'Kişi';
                $result['ae_company'] = 'Şirket';
            }
        }
        return $result;
    }

    public static function testimonial_column(string $column, int $post_id): void {
        if ('ae_language' === $column) {
            echo '<span class="ae-chip">' . esc_html(strtoupper(current_language_for_post($post_id))) . '</span>';
            return;
        }

        if ('ae_person' === $column) {
            $person = trim((string) get_post_meta($post_id, 'ae_person_name', true));
            echo $person !== '' ? esc_html($person) : '<span class="ae-muted">—</span>';
            return;
        }

        if ('ae_company' === $column) {
            $company = trim((string) get_post_meta($post_id, 'ae_company', true));
            echo $company !== '' ? esc_html($company) : '<span class="ae-muted">—</span>';
        }
    }

    private static function register_type(string $post_type, string $plural, string $singular, array $supports, bool $seo_visible = false): void {
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
            // Rank Math discovers SEO-manageable custom post types through their
            // public registration state. Work/Testimonial therefore advertise
            // themselves as public content models, while all native WordPress
            // frontend routes remain explicitly disabled for the headless setup.
            'public' => $seo_visible,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => 'apostrophe-core',
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => false,
            'show_in_rest' => true,
            'supports' => $supports,
            'hierarchical' => false,
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
        ]);
    }
}
