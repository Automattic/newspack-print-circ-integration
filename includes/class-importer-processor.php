<?php
/**
 * Importer Processor.
 *
 * @package Newspack_Print_Circ
 */

namespace Newspack_Print_Circ;

/**
 * Class to handle processing of Importer files.
 */
class Importer_Processor {


	public static function import_users( $limit= 100, $offset = 0 ) {

		$users = self::get_users_to_import( $limit, $offset );

		foreach ( $users as $user ) {
			$user = Import_Parser::parse_line( $user );
			self::process_user( $user );
		}

	}

	public static function get_users_to_import( $limit = 100, $offset = 0 ) {

		$users = self::get_from_csv( $limit, $offset );

		return $users;
	}

	public static function process_user( $user ) {

		$user_id = self::get_or_create_user( $user );

		if ( self::should_update_user( $user_id ) ) {
			self::update_user( $user_id, $user );
		}


	}

	public static function get_or_create_user( $user ) {

		/**
		 * First check by email. If a user with the same email exists, they are the same user.
		 * If this is a user that has been exported before, and we are now importing them again,
		 * we don't want add the circ_id because all info from this user should be managed in the site.
		 * We won't update the user with info coming from the external system.
		 */
		if ( ! empty( $user['email'] ) ) {
			$existing_user = self::get_user_by_email( $user['email'] );
			if ( $existing_user ) {
				return $existing_user->ID;
			}
		}

		$existing_user = self::get_user_by_circ_id( $user['circ_id'] );

		if ( $existing_user ) {
			return $existing_user->ID;
		} else {
			$user_id = self::create_user( $user );
		}

	}

	public static function should_update_user( $user_id ) {

		// Only users that have the circ_id should be updated.
		return (bool) Newspack_Fields::get_field_value( 'circ_id', $user_id );
	}

	public static function create_user( $user ) {

		if ( empty( $user['email'] ) ) {
			$user['email'] = 'no-email-' . uniqid() . '@example.com';
		}

		$user_id = wp_create_user( $user['email'], wp_generate_password(), $user['email'] );

		Newspack_Fields::set_field_value( 'circ_id', $user_id, $user['circ_id'] );

		return $user_id;
	}

	public static function update_user( $user_id, $user ) {

		foreach ( $user_data as $key => $value ) {
			Newspack_Fields::set_field_value( $key, $user_id, $value );
		}

	}


}