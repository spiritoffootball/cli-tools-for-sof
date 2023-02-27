<?php
/**
 * Network utilities for the Spirit of Football website.
 *
 * ## EXAMPLES
 *
 *       # Delete spam comments across the entire network.
 *       $ wp sof network spam-delete
 *       Success: All spam deleted.
 *
 * @since 1.0.0
 *
 * @package Command_Line_Tools_For_SOF
 */
class CLI_Tools_SOF_Command_Network extends CLI_Tools_SOF_Command {

	/**
	 * Delete spam comments across the entire network.
	 *
	 * ## EXAMPLES
	 *
	 *       $ wp sof network spam-delete
	 *       Deleting spam on site https://spiritoffootball.cmw/
	 *       Deleting spam on site https://thebal.cmw/
	 *       Deleting spam on site https://thebal.cmw/2014/
	 *       Deleting spam on site https://thebal.cmw/2010/
	 *       Deleting spam on site https://thebal.cmw/2006/
	 *       Deleting spam on site https://thebal.cmw/2002/
	 *       Deleting spam on site https://spirit-of-germany.cmw/
	 *       Deleting spam on site https://thebal.cmw/2018/
	 *       Deleting spam on site https://spirit-of-germany.cmw/sofgervorstand/
	 *       Deleting spam on site https://spirit-of-germany.cmw/swfk/
	 *       Deleting spam on site https://thebal.cmw/2022/
	 *       Deleting spam on site https://thebal.cmw/2026/
	 *       Deleting spam on site https://spiritoffootball.br.cmw/
	 *       Deleting spam on site https://spirit-of-germany.cmw/mduw/
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

		// Get all Site URLs.
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

		foreach ( $urls as $url ) {

			WP_CLI::log( '' );
			WP_CLI::log( sprintf( WP_CLI::colorize( '%GDeleting spam on site%n %Y%s%n' ), $url ) );

			// Get the spam comment IDs.
			$command = "comment list --status=spam --field=comment_ID --format=json --url='" . untrailingslashit( $url ) . "'";
			WP_CLI::debug( $command, 'sof' );
			$options = [
				'launch' => true,
				'return' => true,
			];
			$spam = WP_CLI::runcommand( $command, $options );

			// Decode the returned JSON array. No other format seemed to work.
			$spam_ids = json_decode( $spam, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				WP_CLI::error( sprintf( WP_CLI::colorize( 'Failed to decode JSON: %Y%s.%n' ), json_last_error_msg() ) );
			}

			// Skip sites with no spam.
			if ( empty( $spam_ids ) ) {

				// Build arguments to delete them.
				$spam_args = implode( ' ', $spam_ids );

				// Delete the spam comments.
				$command = "comment delete {$spam_args} --force --url='" . untrailingslashit( $url ) . "'";
				$options = [
					'launch' => true,
					'return' => true,
				];
				WP_CLI::debug( $command, 'sof' );
				WP_CLI::runcommand( $command, $options );

			}

		}

		WP_CLI::log( '' );
		WP_CLI::success( 'All spam deleted.' );

	}

}
