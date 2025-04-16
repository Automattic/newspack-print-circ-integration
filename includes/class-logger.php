<?php
/**
 * Logger class for Newspack Print Circulation Integration.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

use WP_CLI;

/**
 * Logger class to handle logging.
 */
class Logger {

	/**
	 * The current job ID.
	 *
	 * This will be used to identify the current job in the logs.
	 * The log will write to a file named after the job ID, if one is set.
	 *
	 * @var string
	 */
	private static $job_id;

	/**
	 * Set the job ID.
	 *
	 * @param string $job_id The job ID.
	 */
	public static function set_job_id( $job_id ) {
		self::$job_id = $job_id;
	}

	/**
	 * Add a log entry.
	 *
	 * @param string $message The log message.
	 */
	public static function add_log( $message ) {
		if ( ! empty( self::$job_id ) ) {
			$log_filename = self::$job_id . '.log';
			$log_path = WP_CONTENT_DIR . '/uploads/' . $log_filename;

			// Create the log file if it doesn't exist.
			if ( ! file_exists( $log_path ) ) {
				touch( $log_path );
			}
			// Append the log message to the file.
			$log_message = $message . PHP_EOL;
			file_put_contents( $log_path, $log_message, FILE_APPEND );
		}

		// output the log message to the console if WP-CLI is available.
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::log( $message );
		}
	}
}
