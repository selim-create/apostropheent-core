<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Settings {
    private const PAGE = 'apostrophe-core-settings';
    private const GROUP = 'apostrophe_core_settings';

    public static function boot(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'register']);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
    }

    public static function menu(): void {
        add_menu_page('Apostrophe', 'Apostrophe', 'edit_posts', 'apostrophe-core', [self::class, 'dashboard'], 'dashicons-rest-api', 20);
        add_submenu_page('apostrophe-core', 'Site Ayarları', 'Site Ayarları', 'manage_options', self::PAGE, [self::class, 'render']);
    }

    public static function register(): void {
        foreach (self::fields() as $key => $field) {
            register_setting(self::GROUP, 'apostrophe_core_' . $key, ['sanitize_callback' => $field['sanitize'], 'default' => $field['default'] ?? '']);
        }
    }

    public static function assets(string $hook): void {
        if (!str_contains($hook, 'apostrophe-core')) { return; }
        wp_enqueue_media();
        wp_enqueue_script('apostrophe-core-admin', APOSTROPHE_CORE_URL . 'assets/admin.js', ['jquery'], APOSTROPHE_CORE_VERSION, true);
        wp_enqueue_style('apostrophe-core-admin', APOSTROPHE_CORE_URL . 'assets/admin.css', [], APOSTROPHE_CORE_VERSION);
    }

    public static function dashboard(): void {
        echo '<div class="wrap"><h1>ApostropheEnt Core</h1><p>Apostrophe Entertainment için headless CMS yönetim alanı.</p>';
        echo '<p><code>' . esc_html(rest_url('apostrophe/v1/site?lang=en')) . '</code></p></div>';
    }

    public static function render(): void {
        if (!current_user_can('manage_options')) { return; }
        echo '<div class="wrap"><h1>Apostrophe Site Ayarları</h1><form method="post" action="options.php">';
        settings_fields(self::GROUP);

        $current_group = '';
        foreach (self::fields() as $key => $field) {
            $group = (string) ($field['group'] ?? 'Genel');
            if ($group !== $current_group) {
                if ($current_group !== '') { echo '<hr style="margin:32px 0">'; }
                echo '<h2>' . esc_html($group) . '</h2>';
                $current_group = $group;
            }

            $name = 'apostrophe_core_' . $key;
            $value = get_option($name, $field['default'] ?? '');
            echo '<p><label for="' . esc_attr($name) . '"><strong>' . esc_html($field['label']) . '</strong></label><br>';
            if (($field['input'] ?? 'text') === 'textarea') {
                echo '<textarea class="large-text" rows="4" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">' . esc_textarea((string) $value) . '</textarea>';
            } else {
                echo '<input class="regular-text" type="' . esc_attr($field['input'] ?? 'text') . '" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '">';
            }
            echo '</p>';
        }
        submit_button('Değişiklikleri Kaydet');
        echo '</form></div>';
    }

    public static function fields(): array {
        return [
            'frontend_url' => ['label' => 'Frontend Adresi', 'input' => 'url', 'sanitize' => 'esc_url_raw', 'group' => 'Genel'],
            'site_email' => ['label' => 'E-posta', 'input' => 'email', 'sanitize' => 'sanitize_email', 'group' => 'Genel'],
            'site_phone' => ['label' => 'Telefon', 'sanitize' => 'sanitize_text_field', 'group' => 'Genel'],
            'instagram_url' => ['label' => 'Instagram Adresi', 'input' => 'url', 'sanitize' => 'esc_url_raw', 'group' => 'Genel'],
            'linkedin_url' => ['label' => 'LinkedIn Adresi', 'input' => 'url', 'sanitize' => 'esc_url_raw', 'group' => 'Genel'],
            'london_address' => ['label' => 'Londra Adresi', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Genel'],
            'paris_address' => ['label' => 'Paris Adresi', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Genel'],
            'istanbul_address' => ['label' => 'İstanbul Adresi', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Genel'],

            'work_seo_title_en' => ['label' => 'Projects / Work SEO Başlığı (EN)', 'sanitize' => 'sanitize_text_field', 'group' => 'Liste Sayfaları SEO'],
            'work_seo_description_en' => ['label' => 'Projects / Work Meta Açıklaması (EN)', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Liste Sayfaları SEO'],
            'work_seo_title_fr' => ['label' => 'Projects / Work SEO Başlığı (FR)', 'sanitize' => 'sanitize_text_field', 'group' => 'Liste Sayfaları SEO'],
            'work_seo_description_fr' => ['label' => 'Projects / Work Meta Açıklaması (FR)', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Liste Sayfaları SEO'],
            'testimonials_seo_title_en' => ['label' => 'Testimonials SEO Başlığı (EN)', 'sanitize' => 'sanitize_text_field', 'group' => 'Liste Sayfaları SEO'],
            'testimonials_seo_description_en' => ['label' => 'Testimonials Meta Açıklaması (EN)', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Liste Sayfaları SEO'],
            'testimonials_seo_title_fr' => ['label' => 'Testimonials SEO Başlığı (FR)', 'sanitize' => 'sanitize_text_field', 'group' => 'Liste Sayfaları SEO'],
            'testimonials_seo_description_fr' => ['label' => 'Testimonials Meta Açıklaması (FR)', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Liste Sayfaları SEO'],

            'revalidate_url' => ['label' => 'Revalidation Webhook Adresi', 'input' => 'url', 'sanitize' => 'esc_url_raw', 'group' => 'Teknik'],
            'revalidate_secret' => ['label' => 'Revalidation Gizli Anahtarı', 'input' => 'password', 'sanitize' => 'sanitize_text_field', 'group' => 'Teknik'],
        ];
    }
}
