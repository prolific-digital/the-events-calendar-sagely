<?php
/**
 * Regression check: the Sagely settings form must reject requests without a
 * valid nonce (CSRF) and must not save for users without manage_options.
 *
 * Run: ddev wp eval-file wp-content/plugins/the-events-calendar-sagely/tests/security/settings-csrf.php
 *
 * The plugin must be active. The script restores the original options.
 *
 * @package TecSagely
 */

defined( 'ABSPATH' ) || exit;

use ProlificDigital\SagelyIntegration\Includes\SagelyIntegration;

$tec_sagely_orig_freq = get_option( 'sagely_sync_frequency', null );
$GLOBALS['tec_sagely_failures'] = 0; // eval-file runs in function scope.

/**
 * Print one result line and count failures.
 *
 * @param string $label Test name.
 * @param bool   $ok    Pass or fail.
 */
function tec_sagely_sec_assert( $label, $ok ) {
	if ( ! $ok ) {
		++$GLOBALS['tec_sagely_failures'];
	}
	WP_CLI::log( ( $ok ? 'PASS ' : 'FAIL ' ) . $label );
}

/**
 * Submit the settings form in-process as the given user and report whether it saved.
 *
 * @param int   $user_id User to act as.
 * @param array $post    Simulated $_POST (already slashed, as WordPress does).
 * @return bool True if the sync frequency changed to the submitted value.
 */
function tec_sagely_sec_submit( $user_id, array $post ) {
	wp_set_current_user( $user_id );
	update_option( 'sagely_sync_frequency', 24 );
	$_POST                     = $post;
	$_REQUEST                  = $post;
	$_SERVER['REQUEST_METHOD'] = 'POST';

	// Turn wp_die() (nonce/capability rejection) into an exception.
	$die = static function () {
		return static function () {
			throw new RuntimeException( 'wp_die' );
		};
	};
	add_filter( 'wp_die_handler', $die );
	add_filter( 'wp_die_ajax_handler', $die );

	$plugin = new SagelyIntegration();
	try {
		if ( method_exists( $plugin, 'handle_settings_submission' ) ) {
			$plugin->handle_settings_submission();
		} else {
			// Pre-fix code path: processing happened inside the page render.
			ob_start();
			$plugin->render_settings_page();
			ob_end_clean();
		}
	} catch ( RuntimeException $e ) {
		while ( ob_get_level() > 1 ) {
			ob_end_clean();
		}
	}
	remove_filter( 'wp_die_handler', $die );
	remove_filter( 'wp_die_ajax_handler', $die );
	$_POST    = array();
	$_REQUEST = array();

	return 37 === (int) get_option( 'sagely_sync_frequency' );
}

$tec_sagely_admin = (int) get_user_by( 'login', 'e2e' )->ID;

// 1. Forged request: admin, no nonce.
tec_sagely_sec_assert(
	'admin without nonce is rejected',
	! tec_sagely_sec_submit( $tec_sagely_admin, array( 'sagely_sync_frequency' => '37' ) )
);

// 2. Forged request: admin, wrong nonce.
tec_sagely_sec_assert(
	'admin with a bad nonce is rejected',
	! tec_sagely_sec_submit(
		$tec_sagely_admin,
		array(
			'sagely_sync_frequency' => '37',
			'_wpnonce'              => 'deadbeef',
		)
	)
);

// 3. Legitimate request: admin with a valid nonce saves.
wp_set_current_user( $tec_sagely_admin );
tec_sagely_sec_assert(
	'admin with a valid nonce saves',
	tec_sagely_sec_submit(
		$tec_sagely_admin,
		array(
			'sagely_sync_frequency' => '37',
			'_wpnonce'              => wp_create_nonce( 'tec_sagely_save_settings' ),
		)
	)
);

// 4. Subscriber with a valid nonce for their own session is still refused.
$tec_sagely_sub = wp_insert_user(
	array(
		'user_login' => 'tec_sagely_sec_sub_' . wp_generate_password( 6, false ),
		'user_pass'  => wp_generate_password(),
		'role'       => 'subscriber',
	)
);
wp_set_current_user( $tec_sagely_sub );
tec_sagely_sec_assert(
	'subscriber is rejected',
	! tec_sagely_sec_submit(
		$tec_sagely_sub,
		array(
			'sagely_sync_frequency' => '37',
			'_wpnonce'              => wp_create_nonce( 'tec_sagely_save_settings' ),
		)
	)
);
require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $tec_sagely_sub );

// Restore.
if ( null === $tec_sagely_orig_freq ) {
	delete_option( 'sagely_sync_frequency' );
} else {
	update_option( 'sagely_sync_frequency', $tec_sagely_orig_freq );
}

WP_CLI::log( $GLOBALS['tec_sagely_failures'] ? "{$GLOBALS['tec_sagely_failures']} failure(s)" : 'All settings CSRF checks passed' );
