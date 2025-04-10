<?php
/**
 * Newspack Print Integration Access Manager.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

use WP_Error;

/**
 * Class to manage access and memberships for imported users.
 */
class Access_Manager {

	/**
	 * Get configured membership plans.
	 *
	 * @return array Array of membership plan IDs.
	 */
	public static function get_membership_plans() {
		return Settings::get_setting( Settings::DEFAULT_MEMBERSHIPS_OPTION, [] );
	}

	/**
	 * Grant membership access to a user.
	 *
	 * @param int    $user_id User ID.
	 * @param array  $plan_ids Optional. Specific plan IDs to grant. Default empty array (uses configured plans).
	 * @param string $source Optional. Source of the membership. Default 'print-circ-import'.
	 * @return bool|WP_Error True on success, False on no plans granted, WP_Error on failure.
	 */
	public static function grant_membership_access( $user_id, $plan_ids = [], $source = 'print-circ-import' ) {
		if ( ! function_exists( 'wc_memberships_get_user_membership' ) ) {
			return new WP_Error(
				'membership_not_available',
				__( 'WooCommerce Memberships is not active.', 'newspack-print-circ' )
			);
		}

		$default_plans_to_grant = ! empty( $plan_ids ) ? $plan_ids : self::get_membership_plans();
		$default_plans_to_grant = array_map( 'absint', $default_plans_to_grant );

		if ( empty( $default_plans_to_grant ) ) {
			return false;
		}

		$existing_memberships = self::get_user_active_memberships( $user_id );
		$plans_to_grant       = array_diff( $default_plans_to_grant, $existing_memberships );

		// Log existing memberships.
		if ( ! empty( $existing_memberships ) ) {
			Logger::add_log(
				sprintf(
					'User %d already has active memberships: %s',
					$user_id,
					implode( ', ', $existing_memberships )
				)
			);
		}

		// Log if no new plans to grant.
		if ( empty( $plans_to_grant ) ) {
			return false;
		}

		$granted_plans = [];

		foreach ( $plans_to_grant as $plan_id ) {
			// Create new membership.
			$membership = wc_memberships_create_user_membership( 
				[
					'user_id'         => $user_id,
					'plan_id'         => $plan_id,
					'status'          => Newspack_Fields::get_field_value( Newspack_Fields::STATUS, $user_id ),
					'source'          => $source,
				]
			);

			if ( ! is_wp_error( $membership ) ) {
				$granted_plans[] = $plan_id;
			}
		}

		if ( empty( $granted_plans ) ) {
			return false;
		}

		Logger::add_log(
			sprintf(
				'Granted membership plans %s to user %d',
				implode( ', ', $granted_plans ),
				$user_id
			)
		);

		return true;
	}

	/**
	 * Check if a user has access to a specific membership plan.
	 *
	 * @param int $user_id User ID.
	 * @param int $plan_id Membership plan ID.
	 * @return bool True if user has active membership, false otherwise.
	 */
	public static function has_membership_access( $user_id, $plan_id ) {
		if ( ! function_exists( 'wc_memberships_get_user_membership' ) ) {
			return false;
		}

		$membership = wc_memberships_get_user_membership( $user_id, $plan_id );

		if ( ! $membership ) {
			return false;
		}

		return $membership->is_active();
	}

	/**
	 * Check if a user has access to any of the configured membership plans.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if user has any active membership, false otherwise.
	 */
	public static function has_any_membership_access( $user_id ) {
		$plans = self::get_membership_plans();

		if ( empty( $plans ) ) {
			return false;
		}

		foreach ( $plans as $plan_id ) {
			if ( self::has_membership_access( $user_id, $plan_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get all active memberships for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array Array of active membership plan IDs.
	 */
	public static function get_user_active_memberships( $user_id ) {
		if ( ! function_exists( 'wc_memberships_get_user_memberships' ) ) {
			return [];
		}

		$memberships = wc_memberships_get_user_memberships( $user_id );
		$active_plans = [];

		foreach ( $memberships as $membership ) {
			if ( $membership->is_active() ) {
				$active_plans[] = absint( $membership->get_plan_id() );
			}
		}

		return $active_plans;
	}
}
