<?php
/**
 * Uninstall Biscotti
 *
 * Removes all plugin data from the database when the plugin is deleted.
 *
 * @package Biscotti
 * @author  Jason Cosper <boogah@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Define the meta key (must match the one in biscotti.php).
$meta_key = 'biscotti_login_cookie_expiration';

// Remove user meta for all users.
global $wpdb;

// Delete all user meta entries for this plugin.
$wpdb->delete(
	$wpdb->usermeta,
	array( 'meta_key' => $meta_key ),
	array( '%s' )
);

// Clear any cached user meta.
wp_cache_flush();
