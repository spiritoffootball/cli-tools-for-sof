<?php
/**
 * Network utilities for the Spirit of Football website.
 *
 * ## EXAMPLES
 *
 *       # Delete spam Jetpack Form Submissions on a specific site.
 *       $ wp sof network spam-delete --url=https://spiritoffootball.cmw --type=feedback
 *       Success: All spam feedback deleted.
 *
 *       # Delete spam Comments on a specific site.
 *       $ wp sof network spam-delete --url=https://spiritoffootball.cmw --type=comment
 *       Success: All spam comments deleted.
 *
 *       # Delete spam Comments and Jetpack Form Submissions across the entire network.
 *       $ wp sof network spam-delete --all
 *       Deleting spam on site https://spiritoffootball.cmw
 *       Deleting spam on site https://thebal.cmw
 *       Deleting spam on site https://spirit-of-germany.cmw
 *       Deleting spam on site https://br.spiritoffootball.cmw
 *       Success: All spam deleted.
 *
 * @since 1.0.0
 *
 * @package Command_Line_Tools_For_SOF
 */
class CLI_Tools_SOF_Command_Network extends CLI_Tools_SOF_Command {

	/**
	 * Delete the roles that BBPress added to the Network.
	 *
	 * ## OPTIONS
	 *
	 * [--name=<name>]
	 * : Specify the name of role to remove.
	 *
	 * [--all]
	 * : Run the command for all roles.
	 *
	 * ## EXAMPLES
	 *
	 *       # Remove the roles that BBPress added to the Network.
	 *       $ wp sof network role-delete --url=https://spirit-of-germany.cmw --name=bbp_keymaster
	 *       Deleting role bbp_keymaster on site https://spirit-of-germany.cmw
	 *       Success: Role deleted.
	 *
	 *       # Remove the roles that BBPress added to the Network.
	 *       $ wp sof network role-delete --url=https://spirit-of-germany.cmw
	 *       Deleting role bbp_keymaster on site https://spirit-of-germany.cmw
	 *       Deleting role bbp_spectator on site https://spirit-of-germany.cmw
	 *       Deleting role bbp_blocked on site https://spirit-of-germany.cmw
	 *       Deleting role bbp_moderator on site https://spirit-of-germany.cmw
	 *       Deleting role bbp_participant on site https://spirit-of-germany.cmw
	 *       Success: All roles deleted.
	 *
	 * @subcommand role-delete
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function role_delete( $args, $assoc_args ) {

		// Grab associative arguments.
		$all  = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'all', false );
		$name = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'name', '' );

		// Define BBPress Roles.
		$roles = [
			'bbp_keymaster',
			'bbp_spectator',
			'bbp_blocked',
			'bbp_moderator',
			'bbp_participant',
		];

		// Grab URL from config.
		$url = '';
		$wp_cli_config = WP_CLI::get_config();
		if ( ! empty( $wp_cli_config['url'] ) ) {
			$url = $wp_cli_config['url'];
		}

		// Maybe process all Roles.
		if ( ! empty( $all ) ) {

			// Delete all Roles.
			foreach ( $roles as $role ) {
				WP_CLI::log( sprintf( WP_CLI::colorize( '%GDeleting role %Y%s%n on site%n %Y%s%n' ), $role, $url ) );
				$this->role_delete_from_users( $role );
				remove_role( $role );
			}

			WP_CLI::success( 'All roles deleted.' );

		} else {

			// Show feedback.
			WP_CLI::log( sprintf( WP_CLI::colorize( '%GDeleting role %Y%s%n on site%n %Y%s%n' ), $name, $url ) );

			// Bail if not a BBPress role.
			if ( empty( $name ) ) {
				WP_CLI::error( 'You must specify a role or use the "--all" argument.' );
			}

			// Bail if not a BBPress role.
			if ( ! in_array( $name, $roles ) ) {
				WP_CLI::error( sprintf( WP_CLI::colorize( 'Unknown role: %Y%s%n' ), $name ) );
			}

			// Remove from Users first.
			$this->role_delete_from_users( $name );

			// Now remove the Role.
			remove_role( $name );

			WP_CLI::success( 'Role deleted.' );

		}

	}

	/**
	 * Delete spam comments and JetPack Contact Form Submissions.
	 *
	 * Use the `--all` flag with caution - it has crashed our server in the past. It is,
	 * however, useful for locahost development.
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Specify the type of spam to delete. Accepts 'comment' or 'feedback'. Defaults to 'comment'.
	 *
	 * [--all]
	 * : Run the command across the entire network.
	 *
	 * ## EXAMPLES
	 *
	 *       # Delete spam Jetpack Form Submissions on a specific site.
	 *       $ wp sof network spam-delete --url=https://spiritoffootball.cmw --type=feedback
	 *       Success: All spam feedback deleted.
	 *
	 *       # Delete spam Comments on a specific site.
	 *       $ wp sof network spam-delete --url=https://spiritoffootball.cmw --type=comment
	 *       Success: All spam comments deleted.
	 *
	 *       # Delete spam Comments and Jetpack Form Submissions across the entire network.
	 *       $ wp sof network spam-delete --all
	 *       Deleting spam on site https://spiritoffootball.cmw
	 *       Deleting spam on site https://thebal.cmw
	 *       Deleting spam on site https://spirit-of-germany.cmw
	 *       Deleting spam on site https://br.spiritoffootball.cmw
	 *       Success: All spam deleted.
	 *
	 * @subcommand spam-delete
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function spam_delete( $args, $assoc_args ) {

		// Grab associative arguments.
		$all_sites = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'all', false );
		$type      = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'type', 'comment' );

		// Maybe process all Site URLs.
		if ( ! empty( $all_sites ) ) {

			$options = [
				'launch' => false,
				'return' => true,
			];
			$command = 'site list --field=url --format=json';
			$urls_array = WP_CLI::runcommand( $command, $options );

			// Try and decode response.
			$urls = json_decode( $urls_array, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				WP_CLI::error( sprintf( WP_CLI::colorize( 'Failed to decode JSON: %Y%s.%n' ), json_last_error_msg() ) );
			}

			WP_CLI::debug( print_r( $urls, true ), 'sof' );
			return;

			// Delete all spam for each Site URL.
			foreach ( $urls as $url ) {
				WP_CLI::log( sprintf( WP_CLI::colorize( '%GDeleting spam comments on site%n %Y%s%n' ), $url ) );
				$this->spam_delete_comments( $url );
				WP_CLI::log( sprintf( WP_CLI::colorize( '%GDeleting spam feedback on site%n %Y%s%n' ), $url ) );
				$this->spam_delete_feedback( $url );
			}

			WP_CLI::success( 'All spam deleted.' );

		} else {

			// Grab URL from config.
			$url = '';
			$wp_cli_config = WP_CLI::get_config();
			if ( ! empty( $wp_cli_config['url'] ) ) {
				$url = $wp_cli_config['url'];
			}

			// Show feedback.
			WP_CLI::log( sprintf( WP_CLI::colorize( '%GDeleting spam on site%n %Y%s%n' ), $url ) );

			// Delete for current Site.
			if ( 'comment' === $type ) {
				$this->spam_delete_comments();
				WP_CLI::success( 'All spam comments deleted.' );
			} elseif ( 'feedback' === $type ) {
				$this->spam_delete_feedback();
				WP_CLI::success( 'All spam feedback deleted.' );
			} else {
				WP_CLI::error( sprintf( WP_CLI::colorize( 'Unknown type: %Y%s%n' ), $type ) );
			}

		}

	}

	/**
	 * Deletes spam Comments for a given Site URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The name of the Role.
	 */
	private function role_delete_from_users( $name = '' ) {

		// Build query to get all Users that have the role.
		$args = [
			'role' => $name,
		];

		// Get the Users.
		$users = get_users( $args );

		// Remove the role from any Users that have it.
		foreach ( $users as $user ) {
			$user->remove_role( $name );
		}

	}

	/**
	 * Deletes spam Comments for a given Site URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The URL of the Site.
	 */
	private function spam_delete_comments( $url = '' ) {

		// Launch in current process by default.
		$launch = false;

		// Nothing to add to each command by default.
		$command_extra = '';

		// When we specify a URL.
		if ( ! empty( $url ) ) {

			// Make sure URL has no trailing slash.
			$url = untrailingslashit( $url );

			// Add URL to each command.
			$command_extra = " --url='" . $url . "'";

			// Launch in new process.
			$launch = true;

		}

		// Get the spam Comment IDs.
		$command = 'comment list --status=spam --field=comment_ID --format=json' . $command_extra;
		WP_CLI::debug( $command, 'sof' );
		$options = [
			'launch' => $launch,
			'return' => true,
		];
		$spam = WP_CLI::runcommand( $command, $options );

		// Decode the returned JSON array.
		$spam_ids = json_decode( $spam, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			WP_CLI::error( sprintf( WP_CLI::colorize( 'Failed to decode JSON: %Y%s.%n' ), json_last_error_msg() ) );
		}

		// Skip when there is no spam.
		if ( ! empty( $spam_ids ) ) {

			// Build arguments to delete them.
			$spam_args = implode( ' ', $spam_ids );

			// Delete the spam comments.  Always needs a new process.
			$command = "comment delete {$spam_args} --force" . $command_extra;
			$options = [
				'launch' => true,
				'return' => true,
			];
			WP_CLI::debug( $command, 'sof' );
			WP_CLI::runcommand( $command, $options );

		}

	}

	/**
	 * Deletes spam JetPack Form Submissions for a given Site URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The URL of the Site.
	 */
	private function spam_delete_feedback( $url = '' ) {

		// Launch in current process by default.
		$launch = false;

		// Nothing to add to each command by default.
		$command_extra = '';

		// When we specify a URL.
		if ( ! empty( $url ) ) {

			// Make sure URL has no trailing slash.
			$url = untrailingslashit( $url );

			// Add URL to each command.
			$command_extra = " --url='" . $url . "'";

			// Launch in new process.
			$launch = true;

		}

		// Get the spam JetPack Form Submission IDs.
		$command = 'post list --post_type=feedback --post_status=spam --field=ID --format=json' . $command_extra;
		WP_CLI::debug( $command, 'sof' );
		$options = [
			'launch' => $launch,
			'return' => true,
		];
		$spam = WP_CLI::runcommand( $command, $options );

		// Decode the returned JSON array.
		$spam_ids = json_decode( $spam, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			WP_CLI::error( sprintf( WP_CLI::colorize( 'Failed to decode JSON: %Y%s.%n' ), json_last_error_msg() ) );
		}

		// Skip when there is no spam.
		if ( ! empty( $spam_ids ) ) {

			// Build arguments to delete them.
			$spam_args = implode( ' ', $spam_ids );

			// Delete the spam feedback. Always needs a new process.
			$command = "post delete {$spam_args} --force --quiet" . $command_extra;
			$options = [
				'launch' => true,
				'return' => true,
			];
			WP_CLI::debug( $command, 'sof' );
			$foo = WP_CLI::runcommand( $command, $options );

		}

	}

}
