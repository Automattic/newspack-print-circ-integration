<?php
/**
 * Import Process.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

use Newspack_Print_Circ_Integration_WP_Background_Process as WP_Background_Process;

/**
 * Import Process class.
 * Background process to import the users batch by batch.
 */
class Import_Process extends WP_Background_Process {

	/**
	 * Prefix for the action.
	 *
	 * @var string
	 */
	protected $prefix = 'newspack_print_circ';

	/**
	 * Action.
	 *
	 * @var string
	 */
	protected $action = 'import_process';

	/**
	 * Import the batch of users.
	 *
	 * @param mixed $users Queue users to process.
	 *
	 * @return mixed
	 */
	protected function task( $users ) {
		// Process the users.
		foreach ( $users as $user ) {
			$parsed_user = Import_Parser::parse_line( $user );
			Import::process_user( $parsed_user );
		}

		// False indicates that this task is complete.
		return false;
	}

	/**
	 * Run when all the batches are processed.
	 */
	protected function complete() {
		parent::complete();

		// Log that the import process has completed.
		error_log( 'Import process completed.' ); // phpcs:ignore
	}
}
