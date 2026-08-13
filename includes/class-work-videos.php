<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Work_Videos {
    private const META_KEY = 'ae_videos';
    private const LEGACY_META_KEY = 'ae_video_url';
    private const ORIENTATIONS = ['auto', 'landscape', 'portrait', 'square'];

    public static function get(int $post_id): array {
        $raw = (string) get_post_meta($post_id, self::META_KEY, true);
        $decoded = $raw !== '' ? json_decode($raw, true) : null;
        $videos = is_array($decoded) ? self::normalize($decoded) : [];

        if (!$videos) {
            $legacy_url = esc_url_raw((string) get_post_meta($post_id, self::LEGACY_META_KEY, true));
            if ($legacy_url !== '') {
                $videos[] = [
                    'url' => $legacy_url,
                    'attachment_id' => 0,
                    'orientation' => 'auto',
                    'poster_id' => 0,
                    'title' => '',
                    'featured' => true,
                ];
            }
        }

        return $videos;
    }

    public static function save(int $post_id, $input): void {
        $videos = self::normalize(is_array($input) ? $input : []);

        if (!$videos) {
            delete_post_meta($post_id, self::META_KEY);
            delete_post_meta($post_id, self::LEGACY_META_KEY);
            return;
        }

        // Only one video may be featured. If none is selected, the first one is the default.
        $featured_seen = false;
        foreach ($videos as $index => $video) {
            if ($video['featured'] && !$featured_seen) {
                $featured_seen = true;
                continue;
            }
            $videos[$index]['featured'] = false;
        }
        if (!$featured_seen) {
            $videos[0]['featured'] = true;
        }

        update_post_meta($post_id, self::META_KEY, wp_json_encode($videos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        // Preserve the legacy field for older frontend builds during rollout.
        update_post_meta($post_id, self::LEGACY_META_KEY, $videos[0]['url']);
    }

    public static function payload(int $post_id): array {
        return array_values(array_map(static function (array $video, int $index): array {
            $orientation = self::resolved_orientation($video);
            $source_type = self::source_type($video['url'], (int) $video['attachment_id']);

            return [
                'id' => $index + 1,
                'url' => $video['url'],
                'attachment_id' => (int) $video['attachment_id'],
                'source_type' => $source_type,
                'orientation' => $orientation,
                'poster' => attachment_payload((int) $video['poster_id']),
                'title' => $video['title'],
                'featured' => (bool) $video['featured'],
                'order' => $index + 1,
            ];
        }, self::get($post_id), array_keys(self::get($post_id))));
    }

    private static function normalize(array $videos): array {
        $normalized = [];

        foreach ($videos as $video) {
            if (!is_array($video)) { continue; }
            $url = esc_url_raw((string) ($video['url'] ?? ''));
            if ($url === '') { continue; }

            $orientation = sanitize_key((string) ($video['orientation'] ?? 'auto'));
            if (!in_array($orientation, self::ORIENTATIONS, true)) {
                $orientation = 'auto';
            }

            $normalized[] = [
                'url' => $url,
                'attachment_id' => absint($video['attachment_id'] ?? 0),
                'orientation' => $orientation,
                'poster_id' => absint($video['poster_id'] ?? 0),
                'title' => sanitize_text_field((string) ($video['title'] ?? '')),
                'featured' => !empty($video['featured']) && '0' !== (string) $video['featured'],
            ];
        }

        return $normalized;
    }

    private static function resolved_orientation(array $video): string {
        $orientation = (string) $video['orientation'];
        if ('auto' !== $orientation) { return $orientation; }

        $attachment_id = (int) $video['attachment_id'];
        if ($attachment_id) {
            $metadata = wp_get_attachment_metadata($attachment_id);
            $width = is_array($metadata) ? absint($metadata['width'] ?? 0) : 0;
            $height = is_array($metadata) ? absint($metadata['height'] ?? 0) : 0;
            if ($width && $height) {
                if (abs($width - $height) <= max($width, $height) * 0.08) { return 'square'; }
                return $height > $width ? 'portrait' : 'landscape';
            }
        }

        $poster_id = (int) $video['poster_id'];
        if ($poster_id) {
            $metadata = wp_get_attachment_metadata($poster_id);
            $width = is_array($metadata) ? absint($metadata['width'] ?? 0) : 0;
            $height = is_array($metadata) ? absint($metadata['height'] ?? 0) : 0;
            if ($width && $height) {
                if (abs($width - $height) <= max($width, $height) * 0.08) { return 'square'; }
                return $height > $width ? 'portrait' : 'landscape';
            }
        }

        return 'landscape';
    }

    private static function source_type(string $url, int $attachment_id): string {
        $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host) ?: '';
        if (in_array($host, ['youtube.com', 'm.youtube.com', 'youtu.be'], true)) { return 'youtube'; }
        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true)) { return 'vimeo'; }

        if ($attachment_id) {
            $mime = (string) get_post_mime_type($attachment_id);
            if (str_starts_with($mime, 'video/')) { return 'file'; }
        }

        if (preg_match('/\.(mp4|webm|ogg)(?:$|\?)/i', $url)) { return 'file'; }
        return 'external';
    }
}
