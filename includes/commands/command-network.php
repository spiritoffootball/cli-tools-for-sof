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
	 * Delete spam comments and JetPack Contact Form Submissions.
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
