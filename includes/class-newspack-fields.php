<?php
/**
 * Newspack Print Integration Fields.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

/**
 * Class to handle field mapping and transformations.
 */
class Newspack_Fields {

	/**
	 * Field slugs.
	 */
	const CIRCULATION_ID_FIELD = 'circ_id';
	const FIRST_NAME_FIELD = 'first_name';
	const LAST_NAME_FIELD = 'last_name';
	const DISPLAY_NAME_FIELD = 'display_name';
	const EMAIL_FIELD = 'email';
	const ADDRESS_FIELD = 'address';
	const CITY_FIELD = 'city';
	const STATE_FIELD = 'state';
	const ZIP_FIELD = 'zip';
	const PHONE_FIELD = 'phone';

	/**
	 * Get the fields to be mapped.
	 *
	 * @return array Fields to be mapped.
	 */
	public function get_fields() {
		return [
			self::CIRCULATION_ID_FIELD => [
				'label' => 'Circulation ID',
				'type' => 'user_meta',
				'db_field' => 'newspack_print_circ_circ_id',
			],
			self::FIRST_NAME_FIELD => [
				'label' => 'First Name',
				'type' => 'user_meta',
				'db_field' => 'billing_first_name',
			],
			self::LAST_NAME_FIELD => [
				'label' => 'Last Name',
				'type' => 'user_meta',
				'db_field' => 'billing_last_name',
			],
			self::DISPLAY_NAME_FIELD => [
				'label' => 'Display Name',
				'type' => 'user_prop',
				'db_field' => 'display_name',
			],
			self::EMAIL_FIELD => [
				'label' => 'Email',
				'type' => 'user_prop',
				'db_field' => 'user_email',
			],
			self::ADDRESS_FIELD => [
				'label' => 'Address',
				'type' => 'user_meta',
				'db_field' => 'billing_address_1',
			],
			self::CITY_FIELD => [
				'label' => 'City',
				'type' => 'user_meta',
				'db_field' => 'billing_city',
			],
			self::STATE_FIELD => [
				'label' => 'State',
				'type' => 'user_meta',
				'db_field' => 'billing_state',
			],
			self::ZIP_FIELD => [
				'label' => 'Zip',
				'type' => 'user_meta',
				'db_field' => 'billing_postcode',
			],
			self::PHONE_FIELD => [
				'label' => 'Phone',
				'type' => 'user_meta',
				'db_field' => 'billing_phone',
			],
		];
	}

	/**
	 * Get a specific field.
	 *
	 * @param string $slug Field slug.
	 * @return array Field data.
	 */
	public static function get_field( $slug ) {
		$fields = self::get_fields();
		return $fields[ $slug ];
	}

	/**
	 * Get the user's fields.
	 *
	 * @param int $user_id User ID.
	 * @return array User fields.
	 */
	public static function get_user_as_array( $user_id ) {
		$fields = self::get_fields();
		$user_array = [];
		foreach ( $fields as $slug => $field ) {
			$user_array[ $slug ] = self::get_field_value( $slug, $user_id );
		}
		return $user_array;
	}

	/**
	 * Get the value of a field.
	 *
	 * @param string $slug Field slug.
	 * @param int $user_id User ID.
	 * @return mixed Field value.
	 */
	public static function get_field_value( $slug, $user_id ) {
		$field = self::get_field( $slug );
		if ( $field['type'] === 'user_meta' ) {
			return get_user_meta( $user_id, $field['db_field'], true );
		}
		if ( $field['type'] === 'user_prop' ) {
			$user = get_user_by( 'id', $user_id );
			return $user->{$field['db_field']};
		}
	}

	/**
	 * Set the value of a field.
	 *
	 * @param string $slug Field slug.
	 * @param int $user_id User ID.
	 * @param mixed $value Field value.
	 */
	public static function set_field_value( $slug, $user_id, $value ) {
		$field = self::get_field( $slug );
		if ( $field['type'] === 'user_meta' ) {
			update_user_meta( $user_id, $field['db_field'], $value );
		}
		if ( $field['type'] === 'user_prop' ) {
			$user = get_user_by( 'id', $user_id );
			$user->{$field['db_field']} = $value;
			wp_update_user( $user );
		}
	}
}
