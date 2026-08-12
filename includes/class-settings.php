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
        add_submenu_page('apostrophe-core', 'Site Settings', 'Site Settings', 'manage_options', self::PAGE, [self::class, 'render']);
    }

    public static function register(): void {
        foreach (self::fields() as $key => $field) {
            register_setting(self::GROUP, 'apostrophe_core_' . $key, [
                'sanitize_callback' => $field['sanitize'],
                'default' => $field['default'] ?? '',
            ]);
        }
    }

    public static function assets(string $hook): void {
        if (!str_contains($hook, 'apostrophe-core')) { return; }
        wp_enqueue_media();
        wp_enqueue_script('apostrophe-core-admin', APOSTROPHE_CORE_URL . 'assets/admin.js', ['jquery'], APOSTROPHE_CORE_VERSION, true);
        wp_enqueue_style('apostrophe-core-admin', APOSTROPHE_CORE_URL . 'assets/admin.css', [], APOSTROPHE_CORE_VERSION);
    }

    public static function dashboard(): void {
        echo '<div class="wrap"><h1>ApostropheEnt Core</h1><p>Headless CMS for Apostrophe Entertainment.</p>';
        echo '<p><code>' . esc_html(rest_url('apostrophe/v1/site?lang=en')) . '</code></p></div>';
    }

    public static function render(): void {
        if (!current_user_can('manage_options')) { return; }
        echo '<div class="wrap"><h1>Apostrophe Site Settings</h1><form method="post" action="options.php">';
        settings_fields(self::GROUP);
        foreach (self::fields() as $key => $field) {
            $name = 'apostrophe_core_' . $key;
            $value = get_option($name, $field['default'] ?? '');
            echo '<p><label for="' . esc_attr($name) . '"><strong>' . esc_html($field['label']) . '</strong></label><br>';
            if (($field['input'] ?? 'text') === 'textarea') {
                echo '<textarea class="large-text" rows="4" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">' . esc_textarea((string) $value) . '</textarea>';
            } else {
                echo '<input class="regular-text" type="' . esc_attr($field['input'] ?? 'text') . '" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '">';
            }
            if (!empty($field['description'])) { echo '<br><span class="description">' . esc_html($field['description']) . '</span>'; }
            echo '</p>';
        }
        submit_button();
        echo '</form></div>';
    }

    public static function fields(): array {
        return [
            'frontend_url' => ['label' => 'Frontend URL', 'input' => 'url', 'sanitize' => 'esc_url_raw'],
            'site_email' => ['label' => 'Email', 'input' => 'email', 'sanitize' => 'sanitize_email'],
            'site_phone' => ['label' => 'Phone', 'sanitize' => 'sanitize_text_field'],
            'instagram_url' => ['label' => 'Instagram URL', 'input' => 'url', 'sanitize' => 'esc_url_raw'],
            'linkedin_url' => ['label' => 'LinkedIn URL', 'input' => 'url', 'sanitize' => 'esc_url_raw'],
            'london_address' => ['label' => 'London Address', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
            'paris_address' => ['label' => 'Paris Address', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
            'istanbul_address' => ['label' => 'Istanbul Address', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
            'revalidate_url' => ['label' => 'Revalidation Webhook URL', 'input' => 'url', 'sanitize' => 'esc_url_raw'],
            'revalidate_secret' => ['label' => 'Revalidation Secret', 'input' => 'password', 'sanitize' => 'sanitize_text_field'],
        ];
    }
}
