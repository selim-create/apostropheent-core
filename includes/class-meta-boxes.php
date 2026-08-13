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
            'ae_hero_media_id' => ['Hero Görseli', 'media'],
            'ae_gallery_ids' => ['Galeri Görselleri', 'gallery'],
            'ae_external_label' => ['Dış Bağlantı Etiketi', 'text'],
            'ae_external_url' => ['Dış Bağlantı Adresi', 'url'],
        ] as $key => [$label, $type]) {
            self::field($post->ID, $key, $label, $type);
        }

        self::work_videos($post->ID);
        echo '<p class="description"><strong>Medya kullanımı:</strong> Liste kartı için Öne Çıkan Görseli; detay hero alanı için Hero Görseli; fotoğraflar için Galeri Görsellerini; hareketli içerikler için Videolar bölümünü kullanın. Video sayısı ve yatay/dikey formatı frontend tarafından otomatik düzenlenir.</p>';
    }

    private static function work_videos(int $post_id): void {
        $videos = Work_Videos::get($post_id);
        echo '<div class="ae-field ae-field-videos"><label><strong>Videolar</strong></label><div class="ae-field-control">';
        echo '<div class="ae-video-list" data-next-index="' . esc_attr((string) count($videos)) . '">';
        foreach ($videos as $index => $video) {
            self::video_row((int) $index, $video);
        }
        echo '</div>';
        echo '<button type="button" class="button button-secondary ae-add-video">+ Video Ekle</button>';
        echo '<p class="description">MP4/WebM yükleyebilir veya YouTube/Vimeo URL’si kullanabilirsiniz. WordPress videosunda “Otomatik” format seçilirse mümkün olduğunda medya ölçülerinden yatay/dikey tespit edilir. Dış videolarda formatı elle seçin.</p>';
        echo '<script type="text/html" id="ae-video-row-template">';
        self::video_row('__INDEX__', ['url' => '', 'attachment_id' => 0, 'orientation' => 'auto', 'poster_id' => 0, 'title' => '', 'featured' => false]);
        echo '</script>';
        echo '</div></div>';
    }

    private static function video_row($index, array $video): void {
        $name = 'ae_videos[' . $index . ']';
        $orientation = (string) ($video['orientation'] ?? 'auto');
        $attachment_id = absint($video['attachment_id'] ?? 0);
        $poster_id = absint($video['poster_id'] ?? 0);
        $url = (string) ($video['url'] ?? '');
        $title = (string) ($video['title'] ?? '');
        $featured = !empty($video['featured']);

        echo '<div class="ae-video-card">';
        echo '<div class="ae-video-card-head"><strong class="ae-video-card-title">Video</strong><div class="ae-video-card-actions"><button type="button" class="button-link ae-move-video-up">↑</button><button type="button" class="button-link ae-move-video-down">↓</button><button type="button" class="button-link-delete ae-remove-video">Kaldır</button></div></div>';
        echo '<div class="ae-video-fields">';

        echo '<div class="ae-video-field ae-video-source"><label>Video URL / Dosya</label><div class="ae-video-row"><input type="url" name="' . esc_attr($name . '[url]') . '" value="' . esc_attr($url) . '" class="widefat ae-video-url" placeholder="YouTube, Vimeo veya MP4/WebM adresi"><input type="hidden" class="ae-video-attachment-id" name="' . esc_attr($name . '[attachment_id]') . '" value="' . esc_attr((string) $attachment_id) . '"><button type="button" class="button ae-select-video">Medya Kütüphanesinden Seç</button></div></div>';

        echo '<div class="ae-video-field"><label>Format</label><select name="' . esc_attr($name . '[orientation]') . '">';
        foreach (['auto' => 'Otomatik', 'landscape' => 'Yatay 16:9', 'portrait' => 'Dikey 9:16', 'square' => 'Kare 1:1'] as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($orientation, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></div>';

        echo '<div class="ae-video-field"><label>Başlık <span>(opsiyonel)</span></label><input type="text" name="' . esc_attr($name . '[title]') . '" value="' . esc_attr($title) . '" class="widefat"></div>';

        echo '<div class="ae-video-field ae-video-poster-field"><label>Poster / Kapak <span>(opsiyonel)</span></label><div class="ae-video-poster-preview">' . self::attachment_preview($poster_id) . '</div><input type="hidden" class="ae-video-poster-id" name="' . esc_attr($name . '[poster_id]') . '" value="' . esc_attr((string) $poster_id) . '"><div class="ae-media-row"><button type="button" class="button ae-select-video-poster">Poster Seç</button><button type="button" class="button-link-delete ae-clear-video-poster">Temizle</button></div></div>';

        echo '<div class="ae-video-field ae-video-featured-field"><label><input type="radio" class="ae-video-featured" name="ae_featured_video_index" value="' . esc_attr((string) $index) . '" ' . checked($featured, true, false) . '> Öne çıkan video</label><input type="hidden" class="ae-video-featured-value" name="' . esc_attr($name . '[featured]') . '" value="' . ($featured ? '1' : '0') . '"></div>';

        echo '</div></div>';
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
        echo '<div class="ae-field"><label><strong>' . esc_html($label) . '</strong></label><div class="ae-field-control">';

        if ('select' === $type) {
            $labels = ['pink' => 'Pembe', 'orange' => 'Turuncu', 'blue' => 'Mavi', 'cream' => 'Krem', 'red' => 'Kırmızı'];
            echo '<select name="' . esc_attr($key) . '">';
            foreach ($labels as $accent => $accent_label) {
                printf('<option value="%1$s" %2$s>%3$s</option>', esc_attr($accent), selected($value, $accent, false), esc_html($accent_label));
            }
            echo '</select>';
        } elseif ('media' === $type) {
            $id = absint($value);
            echo '<div class="ae-media-preview">' . self::attachment_preview($id) . '</div>';
            echo '<div class="ae-media-row"><input class="ae-media-id" type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '"><button type="button" class="button ae-select-media">Görsel Seç / Değiştir</button><button type="button" class="button-link-delete ae-clear-media">Temizle</button></div>';
        } elseif ('gallery' === $type) {
            $ids = array_values(array_filter(array_map('absint', explode(',', $value))));
            echo '<div class="ae-gallery-preview">';
            foreach ($ids as $id) { echo self::attachment_preview($id); }
            echo '</div>';
            echo '<div class="ae-media-row"><input class="ae-gallery-ids" type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '"><button type="button" class="button ae-select-gallery">Galeri Seç / Düzenle</button><button type="button" class="button-link-delete ae-clear-gallery">Temizle</button></div>';
        } elseif ('textarea' === $type) {
            echo '<textarea name="' . esc_attr($key) . '" class="widefat" rows="4">' . esc_textarea($value) . '</textarea>';
        } else {
            echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="widefat">';
        }

        echo '</div></div>';
    }

    private static function attachment_preview(int $attachment_id): string {
        if (!$attachment_id) { return '<span class="ae-empty-preview">Görsel seçilmedi</span>'; }
        $image = wp_get_attachment_image($attachment_id, 'thumbnail', false, ['class' => 'ae-preview-image']);
        if (!$image) { return '<span class="ae-empty-preview">Medya #' . esc_html((string) $attachment_id) . '</span>'; }
        return '<div class="ae-preview-item">' . $image . '<span>#' . esc_html((string) $attachment_id) . '</span></div>';
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
        if (isset($_POST['ae_external_url'])) {
            update_post_meta($post_id, 'ae_external_url', esc_url_raw(wp_unslash($_POST['ae_external_url'])));
        }
        if (Content_Types::WORK === $post->post_type) {
            $videos = isset($_POST['ae_videos']) && is_array($_POST['ae_videos']) ? wp_unslash($_POST['ae_videos']) : [];
            Work_Videos::save($post_id, $videos);
        }
    }
}
