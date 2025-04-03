<?php
/**
 * WP-CLI Command for Import Process.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration\CLI;

use \WP_CLI;
use Newspack\PrintCirculationIntegration\Import as Import_Module;
use Newspack\PrintCirculationIntegration\Import_Parser;
use Newspack\PrintCirculationIntegration\Newspack_Fields;
use Newspack\PrintCirculationIntegration\Logger;

class Import {

	/**
	 * Trigger the import process manually.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<batch-size>]
	 * : The size of each batch. Default is 20.
	 *
	 * [--dry-run]
	 * : If set, the import will be a dry run and no changes will be made to the database.
	 *
	 * ## EXAMPLES
	 *
	 *     wp newspack-print import-users --batch-size=50
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public static function import_users( $args, $assoc_args ) {
		$batch_size = isset( $assoc_args['batch-size'] ) ? intval( $assoc_args['batch-size'] ) : 20;
		$is_dry_run = isset( $assoc_args['dry-run'] );

		WP_CLI::log( 'Starting the import process...' );

		// Initialize the import module.
		$import_module = new Import_Module();

		// Fetch the CSV file.
		$fetch_csv_status = $import_module->fetch_csv_file();

		if ( is_wp_error( $fetch_csv_status ) ) {
			WP_CLI::error( 'Error fetching CSV file: ' . $fetch_csv_status->get_error_message() );
			return;
		}

		WP_CLI::line( WP_CLI::colorize( '%CCSV file fetched successfully.%n' ) );

		WP_CLI::log( 'Starting Processing the users...' );

		// Initialize flags and variables.
		$offset        = 0;
		$count         = 0;
		$skipped_users = [];


		/**
		 * TODO: Remove this in the future.
		 */
		if ( $is_dry_run ) {
			WP_CLI::line( WP_CLI::colorize( '%YRunning in Dry run mode. No changes will be made to the database.%n' ) );

			global $wpdb;

			$wpdb->query( 'SET autocommit = 0' );
		}

		// Loop through the CSV file and import users in batches.
		while ( true ) {
			$processed_users = $import_module->get_users_to_import( $batch_size, $offset );

			if ( is_wp_error( $processed_users ) ) {
				return $processed_users;
			}

			/**
			 * Segregate the users into valid and skipped.
			 * Valid users are those that have all the fields.
			 */
			$users         = $processed_users['valid_users'];
			$skipped_users = array_merge( $skipped_users, $processed_users['skipped_users'] );

			if ( empty( $users ) ) {
				// No more users to import.
				break;
			}

			// Process the valid users.
			foreach ( $users as $user ) {
				$parsed_user = Import_Parser::parse_line( $user );
				Import_Module::process_user( $parsed_user );
				WP_CLI::log( sprintf( 'Processed user: %s', $parsed_user[ Newspack_Fields::CIRCULATION_ID_FIELD ] ) );
			}

			$batch_process_message = sprintf( 'Processed Batch %d of %d users.', $count + 1, count( $users ) );
			WP_CLI::line( WP_CLI::colorize( '%g' . $batch_process_message . '%n' ) );

			$offset += $batch_size;
			$count++;
		}

		WP_CLI::success( sprintf( 'Processed a total of %d batches of users.', $count ) );

		// Log the skipped users.
		if ( ! empty( $skipped_users ) ) {
			WP_CLI::line( WP_CLI::colorize( '%Y-------Skipped users with missing data -------%n' ) );

			foreach ( $skipped_users as $skipped_user ) {
				WP_CLI::log( sprintf( 'Skipped user: %s', json_encode( $skipped_user ) ) );
			}
		}

		Logger::add_log( sprintf( 'Processed a total of %d batches of users via CLI.', $count ) );
	}
}
