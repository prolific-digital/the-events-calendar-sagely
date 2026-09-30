<?php
/**
 * Plugin bootstrap, moved unchanged from the main plugin file.
 *
 * @package TecSagely
 */

namespace ProlificDigital\SagelyIntegration;

defined( 'ABSPATH' ) || exit;

// Initialize the plugin
function init() {
  $sagely_integration = new Includes\SagelyIntegration();
}
