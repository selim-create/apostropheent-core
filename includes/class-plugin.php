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
        Settings::boot();
        Rest::boot();
        Polylang::boot();
        Revalidation::boot();
        Security::boot();
        Importer::boot();
        Rank_Math::boot();
    }

    public static function activate(): void {
        Content_Types::register();
        update_option('apostrophe_core_schema_version', APOSTROPHE_CORE_SCHEMA_VERSION, false);
        flush_rewrite_rules(false);
    }

    public static function deactivate(): void {
        flush_rewrite_rules(false);
    }
}
