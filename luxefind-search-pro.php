<?php
/**
 * Plugin Name: LuxeFind Search Pro
 * Description: Privacy-conscious, premium live search for WordPress with optional metasearch providers.
 * Version: 1.0.0-rc.3
 * Requires at least: 6.2
 * Requires PHP: 8.1
 * Author: Independent WordPress Implementation
 * License: GPL-3.0-or-later
 * Text Domain: luxefind-search-pro
 */
if (!defined('ABSPATH')) { exit; }
define('LFXSP_VERSION', '1.0.0-rc.3');
define('LFXSP_FILE', __FILE__);
define('LFXSP_DIR', plugin_dir_path(__FILE__));
require_once LFXSP_DIR . 'includes/class-swp-plugin.php';
add_action('plugins_loaded', array('LFXSP_Plugin', 'boot'));
register_activation_hook(__FILE__, array('LFXSP_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('LFXSP_Plugin', 'deactivate'));
