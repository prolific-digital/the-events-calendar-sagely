<?php
/**
 * Uninstall cleanup for The Events Calendar - Sagely Integration.
 *
 * Removes this plugin's settings (API key, sync frequency) and its scheduled
 * sync. Imported events, venues, categories, tags and the `_sagely_event_id`
 * post meta are deliberately kept: they are the site's calendar content.
 *
 * @package TecSagely
 */

defined( 'ABSPATH' ) || exit;
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete this plugin's options and cron event on the current site.
 */
function tec_sagely_uninstall_site() {
	delete_option( 'sagely_api_key' );
	delete_option( 'sagely_sync_frequency' );
	wp_clear_scheduled_hook( 'sagely_event_sync' );
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $tec_sagely_site_id ) {
		switch_to_blog( $tec_sagely_site_id );
		tec_sagely_uninstall_site();
		restore_current_blog();
	}
} else {
	tec_sagely_uninstall_site();
}
