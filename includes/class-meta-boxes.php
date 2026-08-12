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
        add_meta_box('ae-work-details', 'Work Details', [self::class, 'work_box'], Content_Types::WORK, 'normal', 'high');
        add_meta_box('ae-testimonial-details', 'Testimonial Details', [self::class, 'testimonial_box'], Content_Types::TESTIMONIAL, 'normal', 'high');
    }

    public static function assets(string $hook): void {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) { return; }
        wp_enqueue_media();
        wp_enqueue_script('apostrophe-core-admin', APOSTROPHE_CORE_URL . 'assets/admin.js', ['jquery'], APOSTROPHE_CORE_VERSION, true);
        wp_enqueue_style('apostrophe-core-admin', APOSTROPHE_CORE_URL . 'assets/admin.css', [], APOSTROPHE_CORE_VERSION);
    }

    public static function work_box(\WP_Post $post): void {
        wp_nonce_field(self::ACTION, self::NONCE);
        $fields = [
            'ae_service_label' => ['Service / Project Type', 'text'],
            'ae_year' => ['Year', 'number'],
            'ae_accent' => ['Accent', 'select'],
            'ae_hero_media_id' => ['Hero Media', 'media'],
            'ae_gallery_ids' => ['Gallery', 'gallery'],
            'ae_video_url' => ['Video URL', 'url'],
            'ae_external_label' => ['External Link Label', 'text'],
            'ae_external_url' => ['External Link URL', 'url'],
        ];
        foreach ($fields as $key => [$label, $type]) {
            self::field($post->ID, $key, $label, $type);
        }
        echo '<p class="description">Use the normal Featured Image as the Work listing thumbnail. Use the editor for long-form project copy and Excerpt for the short summary.</p>';
    }

    public static function testimonial_box(\WP_Post $post): void {
        wp_nonce_field(self::ACTION, self::NONCE);
        self::field($post->ID, 'ae_person_name', 'Person Name', 'text');
        self::field($post->ID, 'ae_person_role', 'Job Title', 'text');
        self::field($post->ID, 'ae_company', 'Company', 'text');
        self::field($post->ID, 'ae_accent', 'Accent', 'select');
        echo '<p class="description">Use the main editor for the testimonial quote. The WordPress title can be an internal label such as “OGM Universe — Ekin Karaman Koyuncu”.</p>';
    }

    private static function field(int $post_id, string $key, string $label, string $type): void {
        $value = (string) get_post_meta($post_id, $key, true);
        echo '<div class="ae-field"><label><strong>' . esc_html($label) . '</strong></label>';
        if ('select' === $type) {
            echo '<select name="' . esc_attr($key) . '">';
            foreach (['pink','orange','blue','cream','red'] as $accent) {
                printf('<option value="%1$s" %2$s>%3$s</option>', esc_attr($accent), selected($value, $accent, false), esc_html(ucfirst($accent)));
            }
            echo '</select>';
        } elseif ('media' === $type) {
            echo '<div class="ae-media-row"><input class="ae-media-id" type="number" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '"><button type="button" class="button ae-select-media">Choose Media</button><button type="button" class="button-link-delete ae-clear-media">Clear</button></div>';
        } elseif ('gallery' === $type) {
            echo '<div class="ae-media-row"><input class="ae-gallery-ids" type="text" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" placeholder="12,34,56"><button type="button" class="button ae-select-gallery">Choose Gallery</button><button type="button" class="button-link-delete ae-clear-gallery">Clear</button></div>';
        } else {
            echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="widefat">';
        }
        echo '</div>';
    }

    public static function save(int $post_id, \WP_Post $post): void {
        if (!in_array($post->post_type, [Content_Types::WORK, Content_Types::TESTIMONIAL], true)) { return; }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        if (!current_user_can('edit_post', $post_id)) { return; }
        $nonce = isset($_POST[self::NONCE]) ? sanitize_text_field(wp_unslash($_POST[self::NONCE])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, self::ACTION)) { return; }

        $text_fields = ['ae_service_label','ae_external_label','ae_person_name','ae_person_role','ae_company'];
        foreach ($text_fields as $key) {
            if (isset($_POST[$key])) { update_post_meta($post_id, $key, sanitize_text_field(wp_unslash($_POST[$key]))); }
        }
        if (isset($_POST['ae_year'])) { update_post_meta($post_id, 'ae_year', absint(wp_unslash($_POST['ae_year']))); }
        if (isset($_POST['ae_accent'])) {
            $accent = sanitize_key(wp_unslash($_POST['ae_accent']));
            update_post_meta($post_id, 'ae_accent', in_array($accent, ['pink','orange','blue','cream','red'], true) ? $accent : 'cream');
        }
        foreach (['ae_hero_media_id'] as $key) {
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
