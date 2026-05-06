<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * PHPUnit bootstrap file
 *
 * @package Buddy_Notification_Bell
 */

$bd_bnb_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $bd_bnb_tests_dir ) {
	$bd_bnb_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $bd_bnb_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function bd_bnb_manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/buddy-notification-bell.php';
}
tests_add_filter( 'muplugins_loaded', 'bd_bnb_manually_load_plugin' );

// Start up the WP testing environment.
require $bd_bnb_tests_dir . '/includes/bootstrap.php';
