<?php
/**
 * Biscotti
 *
 * Biscotti is a plugin that modifies the expiration of the logged in user
 * cookie in WordPress to three months, six months, one year, or the default
 * WordPress expiration. Because some people hate to have to keep entering
 * their passwords.
 *
 * @package Biscotti
 * @author  Jason Cosper <boogah@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 * @link    https://github.com/boogah/biscotti
 *
 * @wordpress-plugin
 * Plugin Name:       Biscotti
 * Plugin URI:        https://github.com/boogah/biscotti
 * Description:       Biscotti makes your user's login cookie a little bit longer.
 * Version:           3.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Jason Cosper
 * Author URI:        https://jasoncosper.com/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       biscotti
 * Domain Path:       /languages
 * GitHub Plugin URI: boogah/biscotti
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Plugin constants.
define( 'BISCOTTI_VERSION', '3.0.0' );
define( 'BISCOTTI_META_KEY', 'biscotti_login_cookie_expiration' );
define( 'BISCOTTI_TEXT_DOMAIN', 'biscotti' );
define( 'BISCOTTI_NONCE_ACTION', 'biscotti_update_expiration' );
define( 'BISCOTTI_NONCE_NAME', 'biscotti_nonce' );

// Expiration options constants.
define( 'BISCOTTI_3_MONTHS', '3 months' );
define( 'BISCOTTI_6_MONTHS', '6 months' );
define( 'BISCOTTI_1_YEAR', '1 year' );
define( 'BISCOTTI_DEFAULT', 'default' );

/**
 * Load plugin text domain for translations.
 */
function biscotti_load_textdomain(): void {
	load_plugin_textdomain(
		'biscotti',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'biscotti_load_textdomain' );

/**
 * Add a dropdown menu to the user profile page that allows you to choose the login cookie's expiration date.
 *
 * @param WP_User $user The user object for the profile being edited.
 * @return void
 */
function biscotti_login_cookie_expiration_form_fields( $user ): void {
	$expiration_options  = array(
		BISCOTTI_DEFAULT  => __( 'Default (14 days)', 'biscotti' ),
		BISCOTTI_3_MONTHS => __( '3 months (90 days)', 'biscotti' ),
		BISCOTTI_6_MONTHS => __( '6 months (180 days)', 'biscotti' ),
		BISCOTTI_1_YEAR   => __( '1 year (365 days)', 'biscotti' ),
	);
	$selected_expiration = get_the_author_meta( BISCOTTI_META_KEY, $user->ID );

	// If no value is set, default to 'default'.
	if ( empty( $selected_expiration ) ) {
		$selected_expiration = BISCOTTI_DEFAULT;
	}
	?>
	<h3><?php esc_html_e( 'Login Cookie Expiration', 'biscotti' ); ?></h3>
	<table class="form-table" role="presentation">
	<tr>
		<th><label for="biscotti_login_cookie_expiration"><?php esc_html_e( 'Expiration', 'biscotti' ); ?></label></th>
		<td>
		<select name="biscotti_login_cookie_expiration" id="biscotti_login_cookie_expiration">
			<?php foreach ( $expiration_options as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_expiration, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Choose how long you want to stay logged in. The default WordPress expiration is 14 days. After changing this setting, you will need to log out and back in for it to take effect.', 'biscotti' ); ?>
		</p>
		<?php wp_nonce_field( BISCOTTI_NONCE_ACTION, BISCOTTI_NONCE_NAME ); ?>
		</td>
	</tr>
	</table>
	<?php
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * Manages a user's logged in session cookie expiration via WP-CLI.
	 *
	 * This class provides WP-CLI commands for getting and setting cookie expiration.
	 *
	 * @since 2.1.0
	 */
	class Biscotti_Command {

		/**
		 * Get the logged in session cookie expiration of a user.
		 *
		 * ## OPTIONS
		 *
		 * <user_id>
		 * : ID of the user.
		 *
		 * ## EXAMPLES
		 *
		 *     # Get cookie expiration for user ID 123
		 *     wp biscotti get 123
		 *
		 * @param array $args Positional arguments passed to the command.
		 * @return void
		 */
		public function get( array $args ): void {
			list( $user_id ) = $args;

			// Validate user exists.
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				WP_CLI::error( sprintf( 'User with ID %d not found.', $user_id ) );
				return;
			}

			// Get the user's cookie expiration setting.
			$expiration = get_user_meta( $user_id, BISCOTTI_META_KEY, true );

			// Format output based on the setting.
			$display_value = match ( $expiration ) {
				BISCOTTI_3_MONTHS => '3 months (90 days)',
				BISCOTTI_6_MONTHS => '6 months (180 days)',
				BISCOTTI_1_YEAR => '1 year (365 days)',
				default => 'default (14 days)',
			};

			WP_CLI::line( sprintf( 'Cookie expiration for %s (ID: %d): %s', $user->user_login, $user_id, $display_value ) );
		}

		/**
		 * Set the logged in session cookie expiration of a user.
		 *
		 * ## OPTIONS
		 *
		 * <user_id>
		 * : ID of the user.
		 *
		 * <expiration>
		 * : New expiration duration. One of: default, 3 months, 6 months, 1 year
		 *
		 * ## EXAMPLES
		 *
		 *     # Set cookie expiration to 1 year for user ID 123
		 *     wp biscotti set 123 '1 year'
		 *
		 *     # Reset to default WordPress expiration
		 *     wp biscotti set 123 default
		 *
		 * @param array $args Positional arguments passed to the command.
		 * @return void
		 */
		public function set( array $args ): void {
			list( $user_id, $expiration ) = $args;

			// Validate user exists.
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				WP_CLI::error( sprintf( 'User with ID %d not found.', $user_id ) );
				return;
			}

			// Validate input against allowed values.
			$allowed_values = array(
				BISCOTTI_DEFAULT,
				BISCOTTI_3_MONTHS,
				BISCOTTI_6_MONTHS,
				BISCOTTI_1_YEAR,
			);

			if ( ! in_array( $expiration, $allowed_values, true ) ) {
				WP_CLI::error(
					sprintf(
						'Invalid expiration value "%s". Allowed values: %s',
						$expiration,
						implode( ', ', $allowed_values )
					)
				);
				return;
			}

			// Update the user meta.
			update_user_meta( $user_id, BISCOTTI_META_KEY, $expiration );

			WP_CLI::success( sprintf( 'Updated cookie expiration for %s (ID: %d) to: %s', $user->user_login, $user_id, $expiration ) );
		}
	}

	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::add_command( 'biscotti', 'Biscotti_Command' );
	}
}

// Add the form fields to the user profile page.
add_action( 'show_user_profile', 'biscotti_login_cookie_expiration_form_fields' );
add_action( 'edit_user_profile', 'biscotti_login_cookie_expiration_form_fields' );

/**
 * Update the user meta with the chosen login cookie expiration date.
 *
 * @param int $user_id The ID of the user being updated.
 * @return void
 */
function biscotti_login_cookie_expiration_form_fields_update( int $user_id ): void {
	// Check user capabilities.
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	// Verify nonce for CSRF protection.
	if ( ! isset( $_POST[ BISCOTTI_NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ BISCOTTI_NONCE_NAME ] ) ), BISCOTTI_NONCE_ACTION ) ) {
		return;
	}

	// Validate input against allowed values.
	$allowed_values = array(
		BISCOTTI_DEFAULT,
		BISCOTTI_3_MONTHS,
		BISCOTTI_6_MONTHS,
		BISCOTTI_1_YEAR,
		'', // Legacy support for empty values.
	);
	$value          = isset( $_POST['biscotti_login_cookie_expiration'] ) ? sanitize_text_field( wp_unslash( $_POST['biscotti_login_cookie_expiration'] ) ) : BISCOTTI_DEFAULT;

	// Validate the value is in the allowed list.
	if ( in_array( $value, $allowed_values, true ) ) {
		// If value is empty (legacy), convert to 'default'.
		if ( '' === $value ) {
			$value = BISCOTTI_DEFAULT;
		}

		$updated = update_user_meta( $user_id, BISCOTTI_META_KEY, $value );

		// Add admin notice for successful update (only when editing own profile).
		if ( $updated && get_current_user_id() === $user_id ) {
			add_action( 'user_profile_update_errors', 'biscotti_show_success_notice', 10, 0 );
		}
	}
}

/**
 * Display success notice after updating cookie expiration setting.
 *
 * @return void
 */
function biscotti_show_success_notice(): void {
	add_settings_error(
		'biscotti_messages',
		'biscotti_message',
		__( 'Login cookie expiration updated. Please log out and back in for the change to take effect.', 'biscotti' ),
		'success'
	);
}

// Save the chosen login cookie expiration date when the user profile is updated.
add_action( 'personal_options_update', 'biscotti_login_cookie_expiration_form_fields_update' );
add_action( 'edit_user_profile_update', 'biscotti_login_cookie_expiration_form_fields_update' );

/**
 * Modify the expiration of the logged in user cookie.
 *
 * @param int  $expiration The default expiration time in seconds.
 * @param int  $user_id    The user ID.
 * @param bool $remember   Whether the "remember me" checkbox was checked.
 * @return int The modified expiration time in seconds.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter) -- $remember required by filter signature.
 */
function biscotti_login_cookie_expiration_set_auth_cookie( int $expiration, int $user_id, bool $remember ): int {
	$expiration_time = get_user_meta( $user_id, BISCOTTI_META_KEY, true );

	// Only modify if a custom expiration is set.
	if ( ! empty( $expiration_time ) && BISCOTTI_DEFAULT !== $expiration_time ) {
		switch ( $expiration_time ) {
			case BISCOTTI_3_MONTHS:
				$expiration = 90 * DAY_IN_SECONDS; // Set expiration to 3 months (90 days).
				break;
			case BISCOTTI_6_MONTHS:
				$expiration = 180 * DAY_IN_SECONDS; // Set expiration to 6 months (180 days).
				break;
			case BISCOTTI_1_YEAR:
				$expiration = 365 * DAY_IN_SECONDS; // Set expiration to 1 year (365 days).
				break;
		}
	}

	return $expiration;
}

// Modify the expiration of the logged in user cookie when a user logs into the site.
add_filter( 'auth_cookie_expiration', 'biscotti_login_cookie_expiration_set_auth_cookie', 10, 3 );
