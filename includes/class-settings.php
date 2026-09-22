<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Settings {
    private const PAGE = 'apostrophe-core-settings';
    private const GROUP = 'apostrophe_core_settings';

    public static function boot(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_menu', [self::class, 'order_menu'], 999);
        add_action('admin_init', [self::class, 'register']);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
    }

    public static function menu(): void {
        add_menu_page(
            'Apostrophe',
            'Apostrophe',
            'edit_posts',
            'apostrophe-core',
            [self::class, 'dashboard'],
            'dashicons-layout',
            20
        );
        add_submenu_page('apostrophe-core', 'Dashboard', 'Dashboard', 'edit_posts', 'apostrophe-core', [self::class, 'dashboard']);
        add_submenu_page('apostrophe-core', 'Site Ayarları', 'Site Ayarları', 'manage_options', self::PAGE, [self::class, 'render']);
    }

    public static function order_menu(): void {
        global $submenu;

        if (empty($submenu['apostrophe-core']) || !is_array($submenu['apostrophe-core'])) { return; }

        $priority = [
            'apostrophe-core' => 0,
            'edit.php?post_type=' . Content_Types::WORK => 10,
            'edit.php?post_type=' . Content_Types::TESTIMONIAL => 20,
            'edit.php?post_type=' . Content_Types::HOME => 30,
            'edit.php?post_type=' . Content_Types::SERVICE => 40,
            'edit.php?post_type=' . Content_Types::FIELD => 50,
            self::PAGE => 90,
        ];

        usort($submenu['apostrophe-core'], static function (array $a, array $b) use ($priority): int {
            $a_slug = (string) ($a[2] ?? '');
            $b_slug = (string) ($b[2] ?? '');
            $a_rank = $priority[$a_slug] ?? 70;
            $b_rank = $priority[$b_slug] ?? 70;
            return $a_rank <=> $b_rank;
        });
    }

    public static function register(): void {
        foreach (self::fields() as $key => $field) {
            register_setting(
                self::GROUP,
                'apostrophe_core_' . $key,
                [
                    'sanitize_callback' => $field['sanitize'],
                    'default' => $field['default'] ?? '',
                ]
            );
        }
    }

    public static function assets(string $hook): void {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $post_type = $screen && isset($screen->post_type) ? (string) $screen->post_type : '';
        $apostrophe_types = [
            Content_Types::HOME,
            Content_Types::SERVICE,
            Content_Types::FIELD,
            Content_Types::WORK,
            Content_Types::TESTIMONIAL,
        ];

        $is_core_page = str_contains($hook, 'apostrophe-core');
        $is_content_page = in_array($post_type, $apostrophe_types, true);

        if (!$is_core_page && !$is_content_page) { return; }

        wp_enqueue_style('apostrophe-core-admin', APOSTROPHE_CORE_URL . 'assets/admin.css', [], APOSTROPHE_CORE_VERSION);

        if ($is_core_page || in_array($hook, ['post.php', 'post-new.php'], true)) {
            wp_enqueue_media();
            wp_enqueue_script('apostrophe-core-admin', APOSTROPHE_CORE_URL . 'assets/admin.js', ['jquery'], APOSTROPHE_CORE_VERSION, true);
        }
    }

    public static function dashboard(): void {
        if (!current_user_can('edit_posts')) { return; }

        $frontend = Plugin::frontend_url();
        $work_counts = wp_count_posts(Content_Types::WORK);
        $testimonial_counts = wp_count_posts(Content_Types::TESTIMONIAL);
        $service_counts = wp_count_posts(Content_Types::SERVICE);
        $field_counts = wp_count_posts(Content_Types::FIELD);

        $recent_work = get_posts([
            'post_type' => Content_Types::WORK,
            'post_status' => ['publish', 'draft', 'pending'],
            'posts_per_page' => 6,
            'orderby' => 'modified',
            'order' => 'DESC',
            'suppress_filters' => true,
        ]);

        $stats = [
            [
                'label' => 'Projeler',
                'value' => (int) ($work_counts->publish ?? 0),
                'meta' => sprintf('%d taslak', (int) ($work_counts->draft ?? 0)),
                'url' => admin_url('edit.php?post_type=' . Content_Types::WORK),
            ],
            [
                'label' => 'Müşteri Görüşleri',
                'value' => (int) ($testimonial_counts->publish ?? 0),
                'meta' => sprintf('%d taslak', (int) ($testimonial_counts->draft ?? 0)),
                'url' => admin_url('edit.php?post_type=' . Content_Types::TESTIMONIAL),
            ],
            [
                'label' => 'Hizmetler',
                'value' => (int) ($service_counts->publish ?? 0),
                'meta' => 'Site içeriği',
                'url' => admin_url('edit.php?post_type=' . Content_Types::SERVICE),
            ],
            [
                'label' => 'Alanlar',
                'value' => (int) ($field_counts->publish ?? 0),
                'meta' => 'Site içeriği',
                'url' => admin_url('edit.php?post_type=' . Content_Types::FIELD),
            ],
        ];

        $system = [
            ['label' => 'Frontend', 'ok' => $frontend !== '', 'value' => $frontend !== '' ? $frontend : 'Tanımlı değil'],
            ['label' => 'Polylang', 'ok' => function_exists('pll_get_post_language'), 'value' => function_exists('pll_get_post_language') ? 'Bağlı' : 'Kontrol gerekli'],
            ['label' => 'Rank Math', 'ok' => defined('RANK_MATH_VERSION'), 'value' => defined('RANK_MATH_VERSION') ? 'Bağlı' : 'Kontrol gerekli'],
            ['label' => 'Revalidation', 'ok' => trim((string) get_option('apostrophe_core_revalidate_url', '')) !== '', 'value' => trim((string) get_option('apostrophe_core_revalidate_url', '')) !== '' ? 'Webhook hazır' : 'Tanımlı değil'],
        ];

        echo '<div class="wrap ae-admin-shell ae-dashboard">';
        echo '<div class="ae-admin-hero">';
        echo '<div><span class="ae-kicker">APOSTROPHE ENTERTAINMENT / CMS</span><h1>İçerik Kontrol Merkezi</h1><p>Projeleri, müşteri görüşlerini, site metinlerini ve yayın akışını tek yerden yönetin.</p></div>';
        echo '<div class="ae-admin-hero-actions">';
        echo '<a class="button button-primary button-hero" href="' . esc_url(admin_url('post-new.php?post_type=' . Content_Types::WORK)) . '">Yeni Proje</a>';
        echo '<a class="button button-secondary button-hero" href="' . esc_url($frontend) . '" target="_blank" rel="noopener noreferrer">Frontend’i Aç ↗</a>';
        echo '</div></div>';

        echo '<div class="ae-stat-grid">';
        foreach ($stats as $stat) {
            echo '<a class="ae-stat-card" href="' . esc_url($stat['url']) . '">';
            echo '<span class="ae-stat-label">' . esc_html($stat['label']) . '</span>';
            echo '<strong>' . esc_html(number_format_i18n($stat['value'])) . '</strong>';
            echo '<span class="ae-stat-meta">' . esc_html($stat['meta']) . '</span>';
            echo '</a>';
        }
        echo '</div>';

        echo '<div class="ae-dashboard-grid">';
        echo '<section class="ae-panel ae-panel-wide"><div class="ae-panel-head"><div><span class="ae-panel-eyebrow">İÇERİK</span><h2>Son Güncellenen Projeler</h2></div><a href="' . esc_url(admin_url('edit.php?post_type=' . Content_Types::WORK)) . '">Tümünü gör →</a></div>';

        if ($recent_work) {
            echo '<div class="ae-recent-list">';
            foreach ($recent_work as $post) {
                $lang = strtoupper(current_language_for_post((int) $post->ID));
                $presentation = (string) get_post_meta($post->ID, 'ae_presentation_type', true);
                $presentation_label = 'media_feature' === $presentation ? 'Media Feature' : 'Standard';
                $publisher = trim((string) get_post_meta($post->ID, 'ae_media_publisher', true));
                $edit_url = get_edit_post_link($post->ID, '');
                $front_url = Plugin::post_url($post);
                $status = get_post_status_object($post->post_status);
                $status_label = $status ? $status->label : $post->post_status;

                echo '<article class="ae-recent-item">';
                echo '<div class="ae-recent-main"><div class="ae-recent-title-row"><a href="' . esc_url((string) $edit_url) . '">' . esc_html(get_the_title($post)) . '</a><span class="ae-chip">' . esc_html($lang) . '</span></div>';
                echo '<div class="ae-recent-meta"><span>' . esc_html($presentation_label) . '</span><span>' . esc_html($status_label) . '</span>';
                if ($publisher !== '') { echo '<span>' . esc_html($publisher) . '</span>'; }
                echo '</div></div>';
                echo '<div class="ae-recent-actions"><a href="' . esc_url((string) $edit_url) . '">Düzenle</a><a href="' . esc_url($front_url) . '" target="_blank" rel="noopener noreferrer">Görüntüle ↗</a></div>';
                echo '</article>';
            }
            echo '</div>';
        } else {
            echo '<div class="ae-empty-state"><strong>Henüz proje yok.</strong><p>İlk projeyi oluşturarak içerik akışını başlatabilirsiniz.</p><a class="button button-primary" href="' . esc_url(admin_url('post-new.php?post_type=' . Content_Types::WORK)) . '">Yeni Proje</a></div>';
        }
        echo '</section>';

        echo '<aside class="ae-dashboard-side">';
        echo '<section class="ae-panel"><div class="ae-panel-head"><div><span class="ae-panel-eyebrow">SİSTEM</span><h2>Bağlantı Durumu</h2></div></div><div class="ae-status-list">';
        foreach ($system as $item) {
            echo '<div class="ae-status-row"><div><strong>' . esc_html($item['label']) . '</strong><span>' . esc_html($item['value']) . '</span></div><span class="ae-status-dot ' . ($item['ok'] ? 'is-ok' : 'is-warning') . '">' . ($item['ok'] ? 'Hazır' : 'Kontrol') . '</span></div>';
        }
        echo '</div><a class="ae-panel-link" href="' . esc_url(admin_url('admin.php?page=' . self::PAGE)) . '">Site ayarlarını aç →</a></section>';

        echo '<section class="ae-panel"><div class="ae-panel-head"><div><span class="ae-panel-eyebrow">HIZLI AKSİYONLAR</span><h2>Editör Kısayolları</h2></div></div><div class="ae-action-list">';
        echo '<a href="' . esc_url(admin_url('post-new.php?post_type=' . Content_Types::WORK)) . '"><span>＋</span><div><strong>Yeni proje</strong><small>Work / case study ekle</small></div></a>';
        echo '<a href="' . esc_url(admin_url('post-new.php?post_type=' . Content_Types::TESTIMONIAL)) . '"><span>＋</span><div><strong>Yeni müşteri görüşü</strong><small>Testimonial ekle</small></div></a>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=' . self::PAGE . '#liste-sayfalari-icerik')) . '"><span>✎</span><div><strong>Liste sayfası metinleri</strong><small>Work / Testimonials EN-FR</small></div></a>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=' . self::PAGE . '#liste-sayfalari-seo')) . '"><span>⌁</span><div><strong>Liste sayfası SEO</strong><small>Başlık ve meta açıklamalar</small></div></a>';
        echo '</div></section>';
        echo '</aside></div>';

        echo '<section class="ae-panel ae-workflow-panel"><div class="ae-panel-head"><div><span class="ae-panel-eyebrow">EDİTORYAL AKIŞ</span><h2>Bir projeyi yayına hazırlama</h2></div></div>';
        echo '<div class="ae-workflow-grid">';
        echo '<div><span>01</span><strong>İçerik</strong><p>Başlık, özet, ana metin, hizmet ve yılı tamamlayın.</p></div>';
        echo '<div><span>02</span><strong>Sunum & Medya</strong><p>Standard / Media Feature seçimini yapın; hero, galeri ve videoları ekleyin.</p></div>';
        echo '<div><span>03</span><strong>SEO & Kontrol</strong><p>Rank Math alanlarını tamamlayın, frontend önizlemesini kontrol edin ve yayınlayın.</p></div>';
        echo '</div></section>';

        echo '</div>';
    }

    public static function render(): void {
        if (!current_user_can('manage_options')) { return; }

        $groups = self::group_meta();

        echo '<div class="wrap ae-admin-shell ae-settings-page">';
        echo '<div class="ae-admin-hero ae-admin-hero-compact"><div><span class="ae-kicker">APOSTROPHE / SETTINGS</span><h1>Site Ayarları</h1><p>Global bilgiler, liste sayfası içerikleri, SEO ve teknik bağlantıları tek formda yönetin.</p></div>';
        echo '<div class="ae-admin-hero-actions"><a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=apostrophe-core')) . '">← Dashboard</a></div></div>';

        settings_errors();

        echo '<nav class="ae-settings-nav" aria-label="Ayar bölümleri">';
        foreach ($groups as $group => $meta) {
            echo '<a href="#' . esc_attr($meta['id']) . '">' . esc_html($group) . '</a>';
        }
        echo '</nav>';

        echo '<form method="post" action="options.php" class="ae-settings-form">';
        settings_fields(self::GROUP);

        $fields_by_group = [];
        foreach (self::fields() as $key => $field) {
            $group = (string) ($field['group'] ?? 'Genel');
            $fields_by_group[$group][$key] = $field;
        }

        foreach ($groups as $group => $meta) {
            if (empty($fields_by_group[$group])) { continue; }
            echo '<section class="ae-settings-section" id="' . esc_attr($meta['id']) . '">';
            echo '<div class="ae-settings-section-head"><div><span class="ae-panel-eyebrow">' . esc_html($meta['eyebrow']) . '</span><h2>' . esc_html($group) . '</h2><p>' . esc_html($meta['description']) . '</p></div></div>';
            echo '<div class="ae-settings-card">';

            foreach ($fields_by_group[$group] as $key => $field) {
                self::render_field($key, $field);
            }

            echo '</div></section>';
        }

        echo '<div class="ae-settings-save"><div><strong>Değişiklikleri kaydet</strong><span>Kaydedilen ayarlar API ve frontend tarafından kullanılacaktır.</span></div>';
        submit_button('Değişiklikleri Kaydet', 'primary', 'submit', false);
        echo '</div></form></div>';
    }

    private static function render_field(string $key, array $field): void {
        $name = 'apostrophe_core_' . $key;
        $value = get_option($name, $field['default'] ?? '');
        $input = (string) ($field['input'] ?? 'text');
        $lang = str_ends_with($key, '_fr') ? 'FR' : (str_ends_with($key, '_en') ? 'EN' : '');
        $help = (string) ($field['help'] ?? '');

        echo '<div class="ae-setting-field">';
        echo '<div class="ae-setting-label"><label for="' . esc_attr($name) . '">' . esc_html($field['label']) . '</label>';
        if ($lang !== '') { echo '<span class="ae-lang-badge">' . esc_html($lang) . '</span>'; }
        if ($help !== '') { echo '<p>' . esc_html($help) . '</p>'; }
        echo '</div><div class="ae-setting-control">';

        if ('textarea' === $input) {
            echo '<textarea rows="4" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">' . esc_textarea((string) $value) . '</textarea>';
        } else {
            echo '<input type="' . esc_attr($input) . '" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '">';
        }

        if ('frontend_url' === $key && (string) $value !== '') {
            echo '<a class="ae-inline-link" href="' . esc_url((string) $value) . '" target="_blank" rel="noopener noreferrer">Frontend’i aç ↗</a>';
        }

        echo '</div></div>';
    }

    private static function group_meta(): array {
        return [
            'Genel' => [
                'id' => 'genel',
                'eyebrow' => 'GLOBAL',
                'description' => 'Frontend adresi, iletişim bilgileri ve sosyal hesaplar.',
            ],
            'Liste Sayfaları İçerik' => [
                'id' => 'liste-sayfalari-icerik',
                'eyebrow' => 'CONTENT',
                'description' => 'Work ve Testimonials giriş başlıklarını ve açıklamalarını EN / FR ayrı yönetin.',
            ],
            'Liste Sayfaları SEO' => [
                'id' => 'liste-sayfalari-seo',
                'eyebrow' => 'SEO',
                'description' => 'Liste sayfalarının arama motoru başlıklarını ve meta açıklamalarını düzenleyin.',
            ],
            'Teknik' => [
                'id' => 'teknik',
                'eyebrow' => 'INTEGRATION',
                'description' => 'Revalidation ve frontend entegrasyonu için teknik ayarlar.',
            ],
        ];
    }

    public static function fields(): array {
        return [
            'frontend_url' => ['label' => 'Frontend Adresi', 'input' => 'url', 'sanitize' => 'esc_url_raw', 'group' => 'Genel', 'default' => 'https://apostropheent.vercel.app', 'help' => 'WordPress “Görüntüle / Önizle” bağlantılarının açacağı public frontend.'],
            'site_email' => ['label' => 'E-posta', 'input' => 'email', 'sanitize' => 'sanitize_email', 'group' => 'Genel'],
            'site_phone' => ['label' => 'Telefon', 'sanitize' => 'sanitize_text_field', 'group' => 'Genel'],
            'instagram_url' => ['label' => 'Instagram Adresi', 'input' => 'url', 'sanitize' => 'esc_url_raw', 'group' => 'Genel'],
            'linkedin_url' => ['label' => 'LinkedIn Adresi', 'input' => 'url', 'sanitize' => 'esc_url_raw', 'group' => 'Genel'],
            'london_address' => ['label' => 'Londra Adresi', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Genel'],
            'paris_address' => ['label' => 'Paris Adresi', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Genel'],
            'istanbul_address' => ['label' => 'İstanbul Adresi', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Genel'],

            'work_page_title_en' => ['label' => 'Work Sayfa Başlığı', 'sanitize' => 'sanitize_text_field', 'group' => 'Liste Sayfaları İçerik', 'default' => 'WORK'],
            'work_page_intro_en' => ['label' => 'Work Giriş Metni', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Liste Sayfaları İçerik', 'default' => "A glimpse into the partnerships and campaigns we've had the pleasure of crafting."],
            'work_page_title_fr' => ['label' => 'Work Sayfa Başlığı', 'sanitize' => 'sanitize_text_field', 'group' => 'Liste Sayfaları İçerik', 'default' => 'PROJETS'],
            'work_page_intro_fr' => ['label' => 'Work Giriş Metni', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Liste Sayfaları İçerik', 'default' => 'Un aperçu des collaborations et campagnes que nous avons eu le plaisir d’imaginer et de réaliser.'],
            'testimonials_page_title_en' => ['label' => 'Testimonials Sayfa Başlığı', 'sanitize' => 'sanitize_text_field', 'group' => 'Liste Sayfaları İçerik', 'default' => 'TESTIMONIALS'],
            'testimonials_page_intro_en' => ['label' => 'Testimonials Giriş Metni', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Liste Sayfaları İçerik', 'default' => 'What our clients say about partnering with us'],
            'testimonials_page_title_fr' => ['label' => 'Testimonials Sayfa Başlığı', 'sanitize' => 'sanitize_text_field', 'group' => 'Liste Sayfaları İçerik', 'default' => 'TÉMOIGNAGES'],
            'testimonials_page_intro_fr' => ['label' => 'Testimonials Giriş Metni', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Liste Sayfaları İçerik', 'default' => 'Ce que nos clients disent de leur collaboration avec nous'],

            'work_seo_title_en' => ['label' => 'Work SEO Başlığı', 'sanitize' => 'sanitize_text_field', 'group' => 'Liste Sayfaları SEO'],
            'work_seo_description_en' => ['label' => 'Work Meta Açıklaması', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Liste Sayfaları SEO'],
            'work_seo_title_fr' => ['label' => 'Work SEO Başlığı', 'sanitize' => 'sanitize_text_field', 'group' => 'Liste Sayfaları SEO'],
            'work_seo_description_fr' => ['label' => 'Work Meta Açıklaması', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Liste Sayfaları SEO'],
            'testimonials_seo_title_en' => ['label' => 'Testimonials SEO Başlığı', 'sanitize' => 'sanitize_text_field', 'group' => 'Liste Sayfaları SEO'],
            'testimonials_seo_description_en' => ['label' => 'Testimonials Meta Açıklaması', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Liste Sayfaları SEO'],
            'testimonials_seo_title_fr' => ['label' => 'Testimonials SEO Başlığı', 'sanitize' => 'sanitize_text_field', 'group' => 'Liste Sayfaları SEO'],
            'testimonials_seo_description_fr' => ['label' => 'Testimonials Meta Açıklaması', 'input' => 'textarea', 'sanitize' => 'sanitize_textarea_field', 'group' => 'Liste Sayfaları SEO'],

            'revalidate_url' => ['label' => 'Revalidation Webhook Adresi', 'input' => 'url', 'sanitize' => 'esc_url_raw', 'group' => 'Teknik', 'help' => 'İçerik güncellemelerinde frontend cache’ini yenilemek için kullanılan endpoint.'],
            'revalidate_secret' => ['label' => 'Revalidation Gizli Anahtarı', 'input' => 'password', 'sanitize' => 'sanitize_text_field', 'group' => 'Teknik', 'help' => 'Webhook doğrulaması için gizli anahtar.'],
        ];
    }
}
