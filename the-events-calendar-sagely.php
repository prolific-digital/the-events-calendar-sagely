<?php

/**
 * Plugin Name: The Events Calendar - Sagely Integration
 * Plugin URI:  https://prolificdigital.com
 * Description: Integrates Sagely with The Events Calendar plugin.
 * Version:     1.0.1
 * Author:      Prolific Digital
 * Author URI:  https://prolificdigital.com
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: the-events-calendar-sagely
 * Domain Path: /languages
 */

namespace ProlificDigital\SagelyIntegration;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

// Directly include the class file
require_once plugin_dir_path(__FILE__) . 'includes/SagelyIntegration.php';

// Initialize the plugin
function init() {
  $sagely_integration = new Includes\SagelyIntegration();
}
add_action('plugins_loaded', __NAMESPACE__ . '\\init');
