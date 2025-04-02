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
	 * Option to store the total number of batch.
	 *
	 * @var int
	 */
	const TOTAL_BATCH_OPTION = 'newspack_print_circ_import_process_total_batch';

	/**
	 * Index of the current batch.
	 *
	 * @var int
	 */
	public static $batch_index = 0;

	/**
	 * Import the batch of users.
	 *
	 * @param mixed $users Queue users to process.
	 *
	 * @return mixed
	 */
	protected function task( $users ) {
		// Get the total batch from the option.
		if ( ! $total_batch = get_option( self::TOTAL_BATCH_OPTION ) ) {
			return false;
		}

		// Process the users.
		foreach ( $users as $user ) {
			$parsed_user = Import_Parser::parse_line( $user );
			Import::process_user( $parsed_user );
		}

		// Increment the batch index.
		self::$batch_index++;

		// Log the progress.
		$message = sprintf(
			'Processed batch %d of %d',
			self::$batch_index,
			$total_batch
		);
		Logger::add_log( $message );

		// False indicates that this task is complete.
		return false;
	}

	/**
	 * Run when all the batches are processed.
	 */
	protected function complete() {
		parent::complete();

		// Clear the total batch option.
		delete_option( self::TOTAL_BATCH_OPTION );

		// Log that the import process has completed.
		Logger::add_log( 'Import process completed.' );
	}

	/**
	 * Set the total batches.
	 *
	 * @param int $total_batch Total number of batches to process.
	 */
	public function set_total_batch( $total_batch ) {
		// Set the total batch in the option.
		update_option( self::TOTAL_BATCH_OPTION, $total_batch );
	}
}
