<?php
/**
 * Newspack Print Integration Importer.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

use WP_Error;
use Newspack\PrintCirculationIntegration\Import_Process;

/**
 * Importer class to handle the CSV import.
 */
class Import {

	/**
	 * CSV file path.
	 *
	 * @var string
	 */
	private $csv_path = '';

	/**
	 * Import process.
	 *
	 * @var Newspack_Import_Process
	 */
	protected $import_process;

	/**
	 * Constructor.
	 */
	public function __construct() {
		/**
		 * Initialize the import process.
		 * Background processes needs to be initialized early on in plugins_loaded.
		 */
		// $this->import_process = new Import_Process();
	}

	/**
	 * Set the CSV file path.
	 *
	 * @param string $csv_path CSV file path.
	 */
	public function set_csv_path( $csv_path ) {
		$this->csv_path = $csv_path;
	}

	/**
	 * Import users from the CSV file.
	 *
	 * @param int $batch_size  Number of users to import in a batch.
	 * @param int $offset      Offset for the CSV file.
	 *
	 * @return bool|WP_Error True if the users were imported successfully, WP_Error otherwise.
	 */
	public function import_users( $batch_size = 100, $offset = 0 ) {
		// Get users to import.
		$processed_users = $this->get_users_to_import( $batch_size, $offset );

		if ( is_wp_error( $processed_users ) ) {
			return new WP_Error( 'error', 'Failed to get users to import.' );
		}

		if ( ! isset( $processed_users['valid_users'] ) || empty( $processed_users['valid_users'] ) ) {
			// No more users to import.
			Logger::add_log( 'No more users to import.' );
			return false;
		}

		$this->import_users_batch( $processed_users['valid_users'] );

		return true;
	}

	/**
	 * Test import users from the CSV file.
	 *
	 * @return bool|WP_Error True if the users were imported successfully, WP_Error otherwise.
	 */
	public function test_import_users() {
		$users = $this->get_users_to_import( 10, 0 );

		if ( is_wp_error( $users ) ) {
			return $users;
		}

		if ( empty( $users ) ) {
			return [];
		}

		return $this->test_import_users_batch( $users['valid_users'] );
	}

	/**
	 * Import users from the CSV file.
	 *
	 * @param array $users Users to import (raw CSV lines).
	 *
	 * @return bool|WP_Error True if the users were imported successfully, WP_Error otherwise.
	 */
	public function import_users_batch( $users ) {
		if ( empty( $users ) ) {
			return new WP_Error( 'error', 'No users to import.' );
		}

		foreach ( $users as $user ) {
			Logger::add_log( 'Processing user: ' . print_r( $user, true ) );
			$parsed_user = Import_Parser::parse_line( $user );
			Logger::add_log( 'Parsed user: ' . print_r( $parsed_user, true ) );
			self::process_user( $parsed_user );
			Logger::add_log( '--- finished processing user ---' );
		}
		return true;
	}

	/**
	 * Test import users from the CSV file.
	 *
	 * @param array $users Users to import (raw CSV lines).
	 *
	 * @return array|WP_Error Parsed users or WP_Error if there was an error.
	 */
	public function test_import_users_batch( $users ) {
		if ( empty( $users ) ) {
			return new WP_Error( 'error', 'No users to import.' );
		}

		$parsed_users = [];

		foreach ( $users as $user ) {
			Logger::add_log( 'Test Processing user: ' . print_r( $user, true ) );
			$parsed_user = Import_Parser::parse_line( $user );
			$parsed_users[] = $parsed_user;
			Logger::add_log( 'Parsed user: ' . print_r( $parsed_user, true ) );
			Logger::add_log( '--- finished test processing user ---' );
		}

		return $parsed_users;
	}


	/**
	 * Get users to import from the CSV file.
	 *
	 * @param int $limit  Number of users to import.
	 * @param int $offset Offset.
	 *
	 * @return array|WP_Error Users to import. WP_Error if there was an error.
	 */
	public function get_users_to_import( $limit = 100, $offset = 0 ) {

		Logger::add_log( 'Reading batch of users from csv. Offset: ' . $offset );

		/**
		 * Initialize variables.
		 */
		$valid_users   = [];
		$skipped_users = [];

		// Get & check CSV.
		if ( ! is_readable( $this->csv_path ) ) {
			Logger::add_log( 'No CSV file to import.' );
			return new WP_Error( 'error', 'No CSV file to import.' );
		}

		$csv_file = fopen( $this->csv_path, 'r' ); // phpcs:ignore
		if ( ! $csv_file ) {
			Logger::add_log( 'Failed to open CSV file.' );
			return new WP_Error( 'error', 'Failed to open CSV file.' );
		}

		// Reset the file pointer.
		rewind( $csv_file );

		// Get the header.
		$header = fgetcsv( $csv_file );

		// If csv export fields are not set set them to the header.
		if ( 0 === $offset && empty( Settings::get_setting( Settings::CSV_FIELDS ) ) ) {
			Settings::set_setting( Settings::CSV_FIELDS, $header );
		}

		// Skip rows until the offset is reached.
		$line = 0;
		while ( $line < $offset && ( $row = fgetcsv( $csv_file ) ) !== false ) {
			$line++;
		}

		while ( ( $row = fgetcsv( $csv_file ) ) !== false ) {
			// If number of columns is not equal to header, skip the row.
			if ( count( $row ) !== count( $header ) ) {
				Logger::add_log( 'Invalid row: ' . implode( ';', $row ) );
				$skipped_users[] = $row;
				continue;
			}

			// Create an associative array.
			$row = array_combine( $header, $row );

			$valid_users[] = $row;

			// If the limit is reached, break the loop.
			if ( count( $valid_users ) >= $limit ) {
				break;
			}
		}

		// Close the CSV file.
		fclose( $csv_file );
		Logger::add_log( 'Finished reading batch of users from csv. Offset: ' . $offset );
		Logger::add_log( 'Valid users: ' . count( $valid_users ) );
		Logger::add_log( 'Skipped users: ' . count( $skipped_users ) );

		return [
			'valid_users'   => $valid_users,
			'skipped_users' => $skipped_users,
		];
	}

	/**
	 * Get a user if it exists or create a new user.
	 *
	 * @param array $user User data.
	 */
	public static function get_or_create_user( $user ) {
		Logger::add_log( 'Getting or creating user' );
		/**
		 * Check if the user already exists by email.
		 */
		if ( ! empty( $user[ Newspack_Fields::EMAIL_FIELD ] ) ) {
			Logger::add_log( 'Checking user by email: ' . $user[ Newspack_Fields::EMAIL_FIELD ] );
			$existing_user = self::get_user_by_email( $user[ Newspack_Fields::EMAIL_FIELD ] );
			if ( $existing_user ) {
				Logger::add_log( 'User with matching email found. ID: ' . $existing_user->ID );
				return $existing_user->ID;
			}
		}

		/**
		 * Check if the user already exists by circulation_id.
		 */
		$existing_user = self::get_user_by_circulation_id( $user[ Newspack_Fields::CIRCULATION_ID_FIELD ] );
		Logger::add_log( 'Checking user by circulation_id: ' . $user[ Newspack_Fields::CIRCULATION_ID_FIELD ] );

		if ( $existing_user ) {
			Logger::add_log( 'User with matching circulation_id found. ID: ' . $existing_user->ID );
			return $existing_user->ID;
		} else {
			/**
			 * User does not exist, create a new user.
			 */
			$user_id = self::create_user( $user );
			Logger::add_log( 'User created. ID: ' . $user_id );
		}

		return $user_id;
	}

	/**
	 * Check if the user should be updated.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if the user should be updated, false otherwise.
	 */
	public static function should_update_user( $user_id ) {
		// Only users that have the circulation_id should be updated.
		return (bool) Newspack_Fields::get_field_value( Newspack_Fields::CIRCULATION_ID_FIELD, $user_id );
	}

	/**
	 * Create a new user.
	 *
	 * @param array $user User data.
	 * @return int User ID.
	 */
	public static function create_user( $user ) {

		// If email is empty, generate a dummy email.
		if ( empty( $user[ Newspack_Fields::EMAIL_FIELD ] ) ) {
			$user[ Newspack_Fields::EMAIL_FIELD ] = 'no-email-' . uniqid() . '@newspackprintcirc.com';
		}

		$user_id = wp_create_user(
			$user[ Newspack_Fields::EMAIL_FIELD ],
			wp_generate_password(),
			$user[ Newspack_Fields::EMAIL_FIELD ]
		);

		// Populate the user fields.
		foreach ( $user as $key => $value ) {
			if ( ! empty( $value ) ) {
				Newspack_Fields::set_field_value( $key, $user_id, $value );
			}
		}

		return $user_id;
	}

	/**
	 * Update a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $user User data.
	 *
	 * @return bool|WP_Error True if the user was updated successfully, WP_Error otherwise.
	 */
	public static function update_user( $user_id, $user ) {
		if ( empty( $user_id ) || empty( $user ) || ! is_array( $user ) ) {
			return new WP_Error( 'error', 'Invalid user data.' );
		}

		foreach ( $user as $key => $value ) {
			Newspack_Fields::set_field_value( $key, $user_id, $value );
		}

		return true;
	}

	/**
	 * Process a user.
	 *
	 * @param array $user User data.
	 */
	public static function process_user( $user ) {
		// Get or create the user.
		$user_id = self::get_or_create_user( $user );

		// Update the user if required.
		if ( self::should_update_user( $user_id ) ) {

			if ( ! is_wp_error( self::update_user( $user_id, $user ) ) ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
				Logger::add_log( 'User updated: ' . $user_id );
			} else { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElse
				Logger::add_log( 'Failed to update user: ' . $user_id );
				return;
			}

			// Grant membership plans.
			$grant_membership = Access_Manager::grant_membership_access( $user_id );
			Logger::add_log( 'Checking Membership access for user: ' . $user_id );
			if ( is_wp_error( $grant_membership ) ) {
				Logger::add_log( 'Failed to grant membership access to user: ' . $user_id );
				return;
			} elseif ( false === $grant_membership ) {
				Logger::add_log( 'No new membership access granted to user: ' . $user_id );
				return;
			}
		}
	}

	/**
	 * Get a user by email.
	 *
	 * @param string $email User email.
	 * @return WP_User|false User object if found, false otherwise.
	 */
	public static function get_user_by_email( $email ) {
		$user = \get_user_by( 'email', $email );
		return $user;
	}

	/**
	 * Get a user by circulation_id.
	 *
	 * @param string $circulation_id User circulation_id.
	 * @return WP_User|false User object if found, false otherwise.
	 */
	public static function get_user_by_circulation_id( $circulation_id ) {
		$circulation_field = Newspack_Fields::get_field( Newspack_Fields::CIRCULATION_ID_FIELD );
		$user = get_users(
			[
				'meta_key'   => $circulation_field['db_field'],
				'meta_value' => $circulation_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
			]
		);

		return empty( $user ) ? false : $user[0];
	}
}
