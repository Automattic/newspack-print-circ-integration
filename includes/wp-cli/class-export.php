<?php
/**
 * WP-CLI Command for Export Process.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration\CLI;

use WP_CLI;
use Newspack\PrintCirculationIntegration\Export as Export_Module;
use Newspack\PrintCirculationIntegration\Logger;

/**
 * Handles CSV export operations.
 */
class Export {

	/**
	 * Export users to CSV.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<batch-size>]
	 * : The size of each batch. Default is 20.
	 *
	 * ## EXAMPLES
	 *
	 *     wp newspack-print export-users --batch-size=50
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public static function export_users( $args, $assoc_args ) {
		$batch_size = isset( $assoc_args['batch-size'] ) ? intval( $assoc_args['batch-size'] ) : 20;

		WP_CLI::log( 'Starting the export process...' );

		$export_module = new Export_Module();

		$job_id = 'CLI-export-' . gmdate( 'Y-m-d-H:i:s' );
		WP_CLI::log( 'Job ID: ' . $job_id );
		Logger::set_job_id( $job_id );

		$offset         = 0;
		$total_exported = 0;

		while ( true ) {
			WP_CLI::log( sprintf( 'Processing batch with offset %d and batch size %d...', $offset, $batch_size ) );

			$result = $export_module->export_users( $batch_size, $offset );

			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result->get_error_message() );
				break;
			}

			if ( false === $result ) {
				WP_CLI::log( 'No more users to process. Stopping...' );
				break;
			}

			$total_exported += $batch_size;
			$offset         += $batch_size;
		}

		Logger::add_log( sprintf( 'Exported a total of %d users via CLI.', $total_exported ) );
		WP_CLI::success( sprintf( 'Exported %d users.', $total_exported ) );
	}
}
