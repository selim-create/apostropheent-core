<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Meta_Boxes {
    private const NONCE = 'apostrophe_core_meta_nonce';
    private const ACTION = 'apostrophe_core_save_meta';

    public static function boot(): void {
        add_action('add_meta_boxes', [self::class, 'register']);
        add_action('save_post', [self::class, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
    }

    public static function register(): void {
        add_meta_box('ae-home-details', 'Ana Sayfa İçeriği', [self::class, 'home_box'], Content_Types::HOME, 'normal', 'high');
        add_meta_box('ae-service-details', 'Hizmet Detayları', [self::class, 'service_box'], Content_Types::SERVICE, 'side', 'default');
        add_meta_box('ae-work-details', 'Proje Detayları', [self::class, 'work_box'], Content_Types::WORK, 'normal', 'high');
        add_meta_box('ae-testimonial-details', 'Müşteri Görüşü Detayları', [self::class, 'testimonial_box'], Content_Types::TESTIMONIAL, 'normal', 'high');
    }

    public static function assets(string $hook): void {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) { return; }
        wp_enqueue_media();
        wp_enqueue_script('apostrophe-core-admin', APOSTROPHE_CORE_URL . 'assets/admin.js', ['jquery'], APOSTROPHE_CORE_VERSION, true);
        wp_enqueue_style('apostrophe-core-admin', APOSTROPHE_CORE_URL . 'assets/admin.css', [], APOSTROPHE_CORE_VERSION);
    }

    public static function home_box(\WP_Post $post): void {
        wp_nonce_field(self::ACTION, self::NONCE);
        self::field($post->ID, 'ae_hero_title', 'Hero Başlığı', 'textarea');
        self::field($post->ID, 'ae_about_heading', 'Hakkımızda Başlığı', 'text');
        self::field($post->ID, 'ae_services_heading', 'Hizmetler Başlığı', 'text');
        self::field($post->ID, 'ae_fields_heading', 'Alanlar Başlığı', 'text');
        self::field($post->ID, 'ae_contact_heading', 'İletişim Başlığı', 'text');
        self::field($post->ID, 'ae_hero_desktop_id', 'Masaüstü Hero Medyası', 'media');
        self::field($post->ID, 'ae_hero_mobile_id', 'Mobil Hero Medyası', 'media');
        echo '<p class="description">Ana editörü Hakkımızda metni için kullanın. Her dil için bir Ana Sayfa kaydı oluşturun ve Polylang ile eşleştirin.</p>';
    }

    public static function service_box(\WP_Post $post): void {
        wp_nonce_field(self::ACTION, self::NONCE);
        self::field($post->ID, 'ae_style_key', 'Frontend Stil Anahtarı', 'text');
    }

    public static function work_box(\WP_Post $post): void {
        wp_nonce_field(self::ACTION, self::NONCE);
        foreach ([
            'ae_service_label' => ['Hizmet / Proje Türü', 'text'],
            'ae_year' => ['Yıl', 'number'],
            'ae_accent' => ['Renk Vurgusu', 'select'],
            'ae_hero_media_id' => ['Hero Medyası', 'media'],
            'ae_gallery_ids' => ['Galeri', 'gallery'],
            'ae_video_url' => ['Video Adresi', 'url'],
            'ae_external_label' => ['Dış Bağlantı Etiketi', 'text'],
            'ae_external_url' => ['Dış Bağlantı Adresi', 'url'],
        ] as $key => [$label, $type]) {
            self::field($post->ID, $key, $label, $type);
        }
        echo '<p class="description">Liste görseli için Öne Çıkan Görseli, kısa özet için Özet alanını, detaylı proje metni için ana editörü kullanın.</p>';
    }

    public static function testimonial_box(\WP_Post $post): void {
        wp_nonce_field(self::ACTION, self::NONCE);
        self::field($post->ID, 'ae_person_name', 'Ad Soyad', 'text');
        self::field($post->ID, 'ae_person_role', 'Görev / Ünvan', 'text');
        self::field($post->ID, 'ae_company', 'Şirket', 'text');
        self::field($post->ID, 'ae_accent', 'Renk Vurgusu', 'select');
        echo '<p class="description">Müşteri görüşü metni için ana editörü kullanın.</p>';
    }

    private static function field(int $post_id, string $key, string $label, string $type): void {
        $value = (string) get_post_meta($post_id, $key, true);
        echo '<div class="ae-field"><label><strong>' . esc_html($label) . '</strong></label>';
        if ('select' === $type) {
            $labels = ['pink' => 'Pembe', 'orange' => 'Turuncu', 'blue' => 'Mavi', 'cream' => 'Krem', 'red' => 'Kırmızı'];
            echo '<select name="' . esc_attr($key) . '">';
            foreach ($labels as $accent => $accent_label) {
                printf('<option value="%1$s" %2$s>%3$s</option>', esc_attr($accent), selected($value, $accent, false), esc_html($accent_label));
            }
            echo '</select>';
        } elseif ('media' === $type) {
            echo '<div class="ae-media-row"><input class="ae-media-id" type="number" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '"><button type="button" class="button ae-select-media">Medya Seç</button><button type="button" class="button-link-delete ae-clear-media">Temizle</button></div>';
        } elseif ('gallery' === $type) {
            echo '<div class="ae-media-row"><input class="ae-gallery-ids" type="text" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" placeholder="12,34,56"><button type="button" class="button ae-select-gallery">Galeri Seç</button><button type="button" class="button-link-delete ae-clear-gallery">Temizle</button></div>';
        } elseif ('textarea' === $type) {
            echo '<textarea name="' . esc_attr($key) . '" class="widefat" rows="4">' . esc_textarea($value) . '</textarea>';
        } else {
            echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="widefat">';
        }
        echo '</div>';
    }

    public static function save(int $post_id, \WP_Post $post): void {
        $supported = [Content_Types::HOME, Content_Types::SERVICE, Content_Types::WORK, Content_Types::TESTIMONIAL];
        if (!in_array($post->post_type, $supported, true)) { return; }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        if (!current_user_can('edit_post', $post_id)) { return; }
        $nonce = isset($_POST[self::NONCE]) ? sanitize_text_field(wp_unslash($_POST[self::NONCE])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, self::ACTION)) { return; }

        foreach (['ae_hero_title','ae_about_heading','ae_services_heading','ae_fields_heading','ae_contact_heading','ae_style_key','ae_service_label','ae_external_label','ae_person_name','ae_person_role','ae_company'] as $key) {
            if (isset($_POST[$key])) { update_post_meta($post_id, $key, sanitize_text_field(wp_unslash($_POST[$key]))); }
        }
        if (isset($_POST['ae_year'])) { update_post_meta($post_id, 'ae_year', absint(wp_unslash($_POST['ae_year']))); }
        if (isset($_POST['ae_accent'])) {
            $accent = sanitize_key(wp_unslash($_POST['ae_accent']));
            update_post_meta($post_id, 'ae_accent', in_array($accent, ['pink','orange','blue','cream','red'], true) ? $accent : 'cream');
        }
        foreach (['ae_hero_media_id','ae_hero_desktop_id','ae_hero_mobile_id'] as $key) {
            if (isset($_POST[$key])) { update_post_meta($post_id, $key, absint(wp_unslash($_POST[$key]))); }
        }
        if (isset($_POST['ae_gallery_ids'])) {
            $ids = array_values(array_filter(array_map('absint', explode(',', (string) wp_unslash($_POST['ae_gallery_ids'])))));
            update_post_meta($post_id, 'ae_gallery_ids', implode(',', $ids));
        }
        foreach (['ae_video_url','ae_external_url'] as $key) {
            if (isset($_POST[$key])) { update_post_meta($post_id, $key, esc_url_raw(wp_unslash($_POST[$key]))); }
        }
    }
}
