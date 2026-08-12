<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Security {
    public static function boot(): void {
        add_filter('xmlrpc_enabled', '__return_false');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        add_filter('rest_endpoints', [self::class, 'protect_users']);
    }

    public static function protect_users(array $endpoints): array {
        if (is_user_logged_in()) { return $endpoints; }
        foreach (array_keys($endpoints) as $route) {
            if (preg_match('#^/wp/v2/users(?:/|$)#', $route)) { unset($endpoints[$route]); }
        }
        return $endpoints;
    }
}
