<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Importer {
    public static function boot(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_post_apostrophe_run_legacy_import', [self::class, 'run']);
    }

    public static function menu(): void {
        add_submenu_page('apostrophe-core', 'İçerik Aktarımı', 'İçerik Aktarımı', 'manage_options', 'apostrophe-core-import', [self::class, 'screen']);
    }

    public static function screen(): void {
        if (!current_user_can('manage_options')) { return; }
        $last = get_option('apostrophe_core_last_import_report', []);
        echo '<div class="wrap"><h1>Apostrophe İçerik Aktarımı</h1>';
        echo '<p>Bu araç mevcut Apostrophe içeriklerini WordPress veri modeline aktarır ve daha önce oluşturulmuş seed kayıtlarının dil/eşleştirme bilgilerini onarır.</p>';
        echo '<p><strong>Not:</strong> Apostrophe seed anahtarı taşımayan manuel içeriklere dokunulmaz.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('apostrophe_run_legacy_import');
        echo '<input type="hidden" name="action" value="apostrophe_run_legacy_import">';
        submit_button('İçerikleri Aktar / Onar', 'primary');
        echo '</form>';
        if (is_array($last) && $last) {
            echo '<h2>Son Aktarım Raporu</h2><pre style="background:#fff;padding:16px;border:1px solid #dcdcde;max-width:900px;overflow:auto">' . esc_html(wp_json_encode($last, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
        }
        echo '</div>';
    }

    public static function run(): void {
        if (!current_user_can('manage_options')) { wp_die('Bu işlem için yetkiniz yok.'); }
        check_admin_referer('apostrophe_run_legacy_import');

        $seed = require APOSTROPHE_CORE_DIR . 'data/legacy-seed.php';
        $report = ['created' => [], 'updated' => [], 'translations' => [], 'errors' => []];

        $home_ids = [];
        foreach (['en', 'fr'] as $lang) {
            $home_ids[$lang] = self::upsert(Content_Types::HOME, 'home-' . $lang, $seed['home'][$lang], 0, $lang, $report);
        }
        self::link_translations($home_ids, $report, 'home');

        $order = 0;
        foreach ($seed['services'] as $key => $translations) {
            $ids = [];
            foreach (['en', 'fr'] as $lang) {
                $data = $translations[$lang];
                $data['meta'] = ['ae_style_key' => $key];
                $ids[$lang] = self::upsert(Content_Types::SERVICE, 'service-' . $key . '-' . $lang, $data, $order, $lang, $report);
            }
            self::link_translations($ids, $report, 'service-' . $key);
            $order++;
        }

        $order = 0;
        foreach ($seed['fields'] as $field) {
            $ids = [];
            foreach (['en', 'fr'] as $lang) {
                $ids[$lang] = self::upsert(Content_Types::FIELD, 'field-' . $field['key'] . '-' . $lang, ['title' => $field[$lang], 'content' => ''], $order, $lang, $report);
            }
            self::link_translations($ids, $report, 'field-' . $field['key']);
            $order++;
        }

        $order = 0;
        foreach ($seed['work'] as $work) {
            $content = !empty($work['body']) ? implode("\n\n", array_map(static fn($p) => '<p>' . esc_html((string) $p) . '</p>', $work['body'])) : '';
            self::upsert(Content_Types::WORK, 'work-' . $work['slug'] . '-en', [
                'title' => $work['title'], 'slug' => $work['slug'], 'content' => $content, 'excerpt' => $work['summary'] ?? '',
                'meta' => ['ae_service_label' => $work['service'] ?? '', 'ae_year' => $work['year'] ?? 0, 'ae_accent' => $work['accent'] ?? 'cream', 'ae_external_label' => $work['external_label'] ?? '', 'ae_external_url' => $work['external_url'] ?? ''],
            ], $order, 'en', $report);
            $order++;
        }

        $order = 0;
        foreach ($seed['testimonials'] as $testimonial) {
            $key = sanitize_title($testimonial['name'] . '-' . $testimonial['company']);
            self::upsert(Content_Types::TESTIMONIAL, 'testimonial-' . $key . '-en', [
                'title' => $testimonial['company'] . ' — ' . $testimonial['name'], 'content' => $testimonial['quote'],
                'meta' => ['ae_person_name' => $testimonial['name'], 'ae_person_role' => $testimonial['role'], 'ae_company' => $testimonial['company'], 'ae_accent' => $testimonial['accent'] ?? 'cream'],
            ], $order, 'en', $report);
            $order++;
        }

        update_option('apostrophe_core_last_import_report', $report, false);
        wp_safe_redirect(admin_url('admin.php?page=apostrophe-core-import&imported=1'));
        exit;
    }

    private static function upsert(string $post_type, string $seed_key, array $data, int $order, string $lang, array &$report): int {
        $existing = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => 'apostrophe_seed_key', 'meta_value' => $seed_key, 'suppress_filters' => true]);
        $is_new = !$existing;

        $postarr = [
            'post_type' => $post_type, 'post_status' => 'publish', 'post_title' => (string) ($data['title'] ?? ''),
            'post_name' => sanitize_title((string) ($data['slug'] ?? $seed_key)), 'post_content' => (string) ($data['content'] ?? ''),
            'post_excerpt' => (string) ($data['excerpt'] ?? ''), 'menu_order' => $order,
        ];
        if (!$is_new) { $postarr['ID'] = (int) $existing[0]; }

        $post_id = $is_new ? wp_insert_post($postarr, true) : wp_update_post($postarr, true);
        if (is_wp_error($post_id)) {
            $report['errors'][] = ['key' => $seed_key, 'error' => $post_id->get_error_message()];
            return 0;
        }

        $post_id = (int) $post_id;
        update_post_meta($post_id, 'apostrophe_seed_key', $seed_key);
        foreach (($data['meta'] ?? []) as $key => $value) { update_post_meta($post_id, (string) $key, $value); }

        if (function_exists('pll_set_post_language')) { pll_set_post_language($post_id, $lang); }

        $report[$is_new ? 'created' : 'updated'][] = ['key' => $seed_key, 'id' => $post_id, 'lang' => $lang, 'assigned_language' => function_exists('pll_get_post_language') ? pll_get_post_language($post_id, 'slug') : null];
        return $post_id;
    }

    private static function link_translations(array $ids, array &$report, string $group): void {
        $translations = [];
        foreach (['en', 'fr'] as $lang) {
            if (!empty($ids[$lang])) { $translations[$lang] = absint($ids[$lang]); }
        }
        if (count($translations) < 2 || !function_exists('pll_save_post_translations')) {
            $report['errors'][] = ['group' => $group, 'error' => 'Polylang çeviri eşleştirmesi yapılamadı.'];
            return;
        }
        pll_save_post_translations($translations);
        $verified = [];
        foreach ($translations as $lang => $post_id) {
            $verified[$lang] = function_exists('pll_get_post') ? (int) pll_get_post($post_id, $lang) : $post_id;
        }
        $report['translations'][] = ['group' => $group, 'ids' => $translations, 'verified' => $verified];
    }
}
