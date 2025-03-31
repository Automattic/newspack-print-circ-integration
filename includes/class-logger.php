<?php
/**
 * Logger class for Newspack Print Circulation Integration.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

/**
 * Logger class to handle logging.
 */
class Logger {

	/**
	 * Option name for storing logs.
	 */
	const LOG_OPTION = 'newspack_print_circ_logs';

	/**
	 * Initialize the logger.
	 */
	public static function is_enabled() {
		// Check if logging is enabled in the settings.
		return (bool) Settings::get_setting( Settings::LOGGING_ENABLED_OPTION );
	}

	/**
	 * Add a log entry.
	 *
	 * @param string $message The log message.
	 */
	public static function add_log( $message ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		$logs = get_option( self::LOG_OPTION, [] );

		// Add a timestamp to the log message.
		$logs[] = [
			'time'    => current_time( 'mysql' ),
			'message' => $message,
		];

		// Keep only the last 20 logs.
		if ( count( $logs ) > 20 ) {
			$logs = array_slice( $logs, -20 );
		}

		update_option( self::LOG_OPTION, $logs );
	}

	/**
	 * Get the logs.
	 *
	 * @return array The logs.
	 */
	public static function get_logs() {
		return get_option( self::LOG_OPTION, [] );
	}

	/**
	 * Clear all logs.
	 */
	public static function clear_logs() {
		delete_option( self::LOG_OPTION );
	}
}
