<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

function attachment_payload(int $id): ?array {
    if ($id <= 0) { return null; }
    $url = wp_get_attachment_url($id);
    if (!$url) { return null; }
    $meta = wp_get_attachment_metadata($id);
    return [
        'id' => $id,
        'url' => esc_url_raw($url),
        'alt' => (string) get_post_meta($id, '_wp_attachment_image_alt', true),
        'mime' => (string) get_post_mime_type($id),
        'width' => isset($meta['width']) ? (int) $meta['width'] : null,
        'height' => isset($meta['height']) ? (int) $meta['height'] : null,
    ];
}

function gallery_payload(string $raw): array {
    $ids = array_values(array_filter(array_map('absint', explode(',', $raw))));
    $items = [];
    foreach ($ids as $id) {
        $payload = attachment_payload($id);
        if ($payload) { $items[] = $payload; }
    }
    return $items;
}

function current_language_for_post(int $post_id): string {
    if (function_exists('pll_get_post_language')) {
        $lang = pll_get_post_language($post_id, 'slug');
        if (is_string($lang) && $lang !== '') { return Polylang::logical_language($lang); }
    }
    return 'en';
}
