<?php

declare(strict_types=1);

namespace ApostropheEnt\Core;

if (!defined('ABSPATH')) { exit; }

final class Plugin {
    private static ?self $instance = null;

    private function __construct() {}

    public static function instance(): self {
        if (null === self::$instance) { self::$instance = new self(); }
        return self::$instance;
    }

    public function boot(): void {
        Content_Types::boot();
        Meta_Boxes::boot();
        Rest::boot();
        Polylang::boot();
    }

    public static function activate(): void {
        Content_Types::register();
        flush_rewrite_rules(false);
    }
}
