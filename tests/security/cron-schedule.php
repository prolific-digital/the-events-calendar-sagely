<?php
/**
 * Regression check: the sagely_event_sync cron event is scheduled on
 * activation, re-created by the self-heal on sites that are missing it, and
 * cleared on deactivation.
 *
 * Run: ddev wp eval-file wp-content/plugins/the-events-calendar-sagely/tests/security/cron-schedule.php
 *
 * The plugin must be active. The script restores the original options and
 * leaves the event scheduled only if it was scheduled before.
 *
 * @package TecSagely
 */

defined( 'ABSPATH' ) || exit;

use ProlificDigital\SagelyIntegration\Includes\SagelyIntegration;

$tec_sagely_orig_key   = get_option( 'sagely_api_key', null );
$tec_sagely_had_event  = (bool) wp_next_scheduled( 'sagely_event_sync' );
$tec_sagely_basename   = plugin_basename( TEC_SAGELY_PLUGIN_FILE );
$GLOBALS['tec_sagely_cron_fails'] = 0; // eval-file runs in function scope.

/**
 * Print one result line and count failures.
 *
 * @param string $label Test name.
 * @param bool   $ok    Pass or fail.
 */
function tec_sagely_cron_assert( $label, $ok ) {
	if ( ! $ok ) {
		++$GLOBALS['tec_sagely_cron_fails'];
	}
	WP_CLI::log( ( $ok ? 'PASS ' : 'FAIL ' ) . $label );
}

update_option( 'sagely_api_key', 'test-key-cron-check' );

// Activation (fires the hook the way activate_plugin() does).
wp_clear_scheduled_hook( 'sagely_event_sync' );
do_action( 'activate_' . $tec_sagely_basename, false );
tec_sagely_cron_assert( 'activation schedules sagely_event_sync', (bool) wp_next_scheduled( 'sagely_event_sync' ) );
$tec_sagely_schedule = wp_get_schedule( 'sagely_event_sync' );
tec_sagely_cron_assert( "recurrence is custom_sagely_interval (got '{$tec_sagely_schedule}')", 'custom_sagely_interval' === $tec_sagely_schedule );

// Deactivation.
do_action( 'deactivate_' . $tec_sagely_basename, false );
tec_sagely_cron_assert( 'deactivation clears sagely_event_sync', ! wp_next_scheduled( 'sagely_event_sync' ) );

// Self-heal: an already-active, configured site with no event.
tec_sagely_cron_assert(
	'self-heal is hooked on init',
	false !== has_action( 'init', array( SagelyIntegration::class, 'schedule_event_sync' ) )
);
SagelyIntegration::schedule_event_sync();
tec_sagely_cron_assert( 'self-heal schedules the missing event', (bool) wp_next_scheduled( 'sagely_event_sync' ) );

// Self-heal does nothing on an unconfigured site.
wp_clear_scheduled_hook( 'sagely_event_sync' );
delete_option( 'sagely_api_key' );
SagelyIntegration::schedule_event_sync();
tec_sagely_cron_assert( 'no event without an API key', ! wp_next_scheduled( 'sagely_event_sync' ) );

// Restore.
if ( null === $tec_sagely_orig_key ) {
	delete_option( 'sagely_api_key' );
} else {
	update_option( 'sagely_api_key', $tec_sagely_orig_key );
}
wp_clear_scheduled_hook( 'sagely_event_sync' );
if ( $tec_sagely_had_event ) {
	SagelyIntegration::schedule_event_sync();
}

WP_CLI::log( $GLOBALS['tec_sagely_cron_fails'] ? "{$GLOBALS['tec_sagely_cron_fails']} failure(s)" : 'All cron checks passed' );
