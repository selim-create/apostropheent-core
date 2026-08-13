<?php
/**
 * Plugin Name: ApostropheEnt Core
 * Plugin URI: https://github.com/selim-create/apostropheent-core
 * Description: Headless WordPress core for Apostrophe Entertainment.
 * Version: 0.3.8
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Hip Medya
 * Text Domain: apostropheent-core
 */

declare(strict_types=1);

if (!defined('ABSPATH')) { exit; }

if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

define('APOSTROPHE_CORE_VERSION', '0.3.8');
define('APOSTROPHE_CORE_SCHEMA_VERSION', '3');
define('APOSTROPHE_CORE_DIR', plugin_dir_path(__FILE__));
define('APOSTROPHE_CORE_URL', plugin_dir_url(__FILE__));

require_once APOSTROPHE_CORE_DIR . 'includes/helpers.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-content-types.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-meta-boxes.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-settings.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-rest.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-polylang.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-revalidation.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-security.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-importer.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-plugin.php';

register_activation_hook(__FILE__, ['ApostropheEnt\\Core\\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['ApostropheEnt\\Core\\Plugin', 'deactivate']);

ApostropheEnt\Core\Plugin::instance()->boot();
