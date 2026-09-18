<?php

/**
 * Plugin Name: Dev Mode
 * Plugin URI: https://www.pestcontrolexperts.com
 * Description: Dashboard widget with debug toggles, dev plugin controls, site snapshot, and environment detection
 * Version: 2.1.0
 * Author: Josh Robbs
 * Requires PHP: 8.2
 * Text Domain: dev-mode
 */

declare(strict_types=1);

namespace DevMode;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load Composer autoloader
$autoloader = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

// Bootstrap the plugin
Bootstrap::init(__FILE__);

// Self-update via self-hosted JSON metadata (published as a release asset by the release workflow)
PucFactory::buildUpdateChecker(
    'https://github.com/JWRDCLLC/dev_mode_plugin/releases/latest/download/details.json',
    __FILE__,
    'dev-mode-plugin'
);
