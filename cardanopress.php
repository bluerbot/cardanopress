<?php

/**
 * Plugin Name: CardanoPress-CHANGE-ME
 * Plugin URI:  https://github.com/CardanoPress/cardanopress
 * Author:      CardanoPress
 * Author URI:  https://cardanopress.io
 * Description: Core plugin for the suite of CardanoPress plugins
 * Version:     1.0.0
 * License:     GPL-2.0-only
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package ThemePlate
 * @since   0.1.0
 */

// Accessed directly
if (! defined('ABSPATH')) {
    exit;
}

use PBWebDev\CardanoPress\Application;
use PBWebDev\CardanoPress\Installer;

/* ==================================================
Global constants
================================================== */

if (! defined('CARDANOPRESS_FILE')) {
    define('CARDANOPRESS_FILE', __FILE__);
}

// Load the main plugin class
require_once plugin_dir_path(CARDANOPRESS_FILE) . 'vendor/autoload.php';

function cardanoPress(): Application
{
    static $application;

    if (null === $application) {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $application = new Application(CARDANOPRESS_FILE);
    }

    return $application;
}

// Instantiate
cardanoPress()->setupHooks();
(new Installer(cardanoPress()))->setupHooks();
