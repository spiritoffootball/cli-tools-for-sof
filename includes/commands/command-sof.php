<?php
/**
 * Manage the Spirit of Football website through the command-line.
 *
 * ## EXAMPLES
 *
 *       # Download the latest stable CiviCRM core archive.
 *       $ wp sof core download
 *
 * @since 1.0.0
 *
 * @package Command_Line_Tools_For_SOF
 */
class CLI_Tools_SOF_Command extends CLI_Tools_SOF_Command_Base {

	/**
	 * Adds our description and sub-commands.
	 *
	 * @since 1.0.0
	 *
	 * @param object $command The command.
	 * @return array $info The array of information about the command.
	 */
	private function command_to_array( $command ) {

		$info = [
			'name' => $command->get_name(),
			'description' => $command->get_shortdesc(),
			'longdesc' => $command->get_longdesc(),
		];

		foreach ( $command->get_subcommands() as $subcommand ) {
			$info['subcommands'][] = $this->command_to_array( $subcommand );
		}

		if ( empty( $info['subcommands'] ) ) {
			$info['synopsis'] = (string) $command->get_synopsis();
		}

		return $info;

	}

}
