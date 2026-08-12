<?php
/**
 * Plugin Name: ApostropheEnt Core
 * Description: Headless WordPress content core for Apostrophe Entertainment.
 * Version: 0.2.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Hip Medya
 */

declare(strict_types=1);

if (!defined('ABSPATH')) { exit; }

define('APOSTROPHE_CORE_VERSION', '0.2.0');
define('APOSTROPHE_CORE_DIR', plugin_dir_path(__FILE__));
define('APOSTROPHE_CORE_URL', plugin_dir_url(__FILE__));

require_once APOSTROPHE_CORE_DIR . 'includes/helpers.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-content-types.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-meta-boxes.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-rest.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-polylang.php';
require_once APOSTROPHE_CORE_DIR . 'includes/class-plugin.php';

register_activation_hook(__FILE__, ['ApostropheEnt\\Core\\Plugin', 'activate']);
ApostropheEnt\Core\Plugin::instance()->boot();
