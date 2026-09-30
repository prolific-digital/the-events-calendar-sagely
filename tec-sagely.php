<?php
/**
 * Plugin Name:       The Events Calendar - Sagely Integration
 * Plugin URI:        https://prolificdigital.com
 * Description:       Imports Sagely community events into The Events Calendar on a schedule.
 * Version:           1.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.4
 * Requires Plugins:  the-events-calendar
 * Author:            Prolific Digital
 * Author URI:        https://prolificdigital.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tec-sagely
 * Domain Path:       /languages
 * Update URI:        https://api.prolificdigital.io/api/update?plugin=tec-sagely
 *
 * @package TecSagely
 */

defined( 'ABSPATH' ) || exit;

define( 'TEC_SAGELY_VERSION', '1.1.0' );
define( 'TEC_SAGELY_PLUGIN_FILE', __FILE__ );
define( 'TEC_SAGELY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Composer dependencies (plugin-update-checker). Present in release zips;
// in a git checkout run `composer install` first.
if ( is_readable( TEC_SAGELY_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once TEC_SAGELY_PLUGIN_DIR . 'vendor/autoload.php';
}

require_once TEC_SAGELY_PLUGIN_DIR . 'includes/update-checker.php';

/**
 * Load bundled translations from /languages (the header's Domain Path).
 */
add_action(
	'init',
	static function () {
		load_plugin_textdomain( 'tec-sagely', false, dirname( plugin_basename( TEC_SAGELY_PLUGIN_FILE ) ) . '/languages' );
	}
);

// Directly include the class file.
require_once TEC_SAGELY_PLUGIN_DIR . 'includes/SagelyIntegration.php';

// Activation hooks must be registered from the main plugin file while it is
// being loaded; registering them later (e.g. on plugins_loaded) means they
// never fire.
register_activation_hook( TEC_SAGELY_PLUGIN_FILE, array( ProlificDigital\SagelyIntegration\Includes\SagelyIntegration::class, 'activate' ) );
register_deactivation_hook( TEC_SAGELY_PLUGIN_FILE, array( ProlificDigital\SagelyIntegration\Includes\SagelyIntegration::class, 'deactivate' ) );

// The namespaced ProlificDigital\SagelyIntegration\init() bootstrap lives in
// includes/bootstrap.php so this file can start with the ABSPATH guard
// (a namespace declaration must be a file's first statement).
require_once TEC_SAGELY_PLUGIN_DIR . 'includes/bootstrap.php';
add_action( 'plugins_loaded', 'ProlificDigital\\SagelyIntegration\\init' );
