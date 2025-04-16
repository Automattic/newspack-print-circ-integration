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
	 * @param string $source Optional. Source of the membership. Default 'newspack-print-circ-import'.
	 * @return bool|WP_Error True on success, False on no plans granted, WP_Error on failure.
	 */
	public static function grant_membership_access( $user_id, $plan_ids = [], $source = 'newspack-print-circ-import' ) {
		if ( ! class_exists( 'WC_Memberships_User_Membership' ) ) {
			return new WP_Error(
				'missing_memberships_class',
				__( 'WooCommerce Memberships is not active.', 'newspack-print' )
			);
		}

		/**
		 * Set default plans to grant.
		 */
		$default_plans_to_grant = ! empty( $plan_ids ) ? $plan_ids : self::get_membership_plans();
		$default_plans_to_grant = array_map( 'absint', $default_plans_to_grant );
		$user_status            = Newspack_Fields::get_field_value( Newspack_Fields::STATUS, $user_id );

		if ( empty( $default_plans_to_grant ) ) {
			return false;
		}

		/**
		 * Check for which plans to update and which to grant.
		 */
		$existing_memberships = self::get_user_memberships( $user_id );

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

		/**
		 * Next we update the status of any existing memberships that are also in the default plans to grant.
		 * This is to ensure that if a user already has a membership, we can update its status.
		 * This is useful for cases where the user has a membership but its status changes.
		 */
		$plans_to_update = array_intersect( $default_plans_to_grant, $existing_memberships );

		// Update existing memberships.
		if ( ! empty( $plans_to_update ) ) {
			foreach ( $plans_to_update as $plan_id ) {
				$membership = wc_memberships_get_user_membership( $user_id, $plan_id );
	
				if ( ! is_wp_error( $membership ) ) {
					// Update status.
					$membership->update_status( $user_status, 'Membership status updated via CSV import.');
				}
			}

			Logger::add_log(
				sprintf(
					'Updated membership plans %s for user %d',
					implode( ', ', $plans_to_update ),
					$user_id
				)
			);
		}

		/**
		 * Now we check for which plans to grant.
		 * New User Memberships are created for any plans that are in the default plans to grant but not in the existing memberships.
		 */
		$plans_to_grant = array_diff( $default_plans_to_grant, $existing_memberships );

		// Log if no new plans to grant.
		if ( empty( $plans_to_grant ) ) {
			return false;
		}

		$granted_plans = [];

		foreach ( $plans_to_grant as $plan_id ) {
			// Create new membership.
			$membership = wc_memberships_create_user_membership( 
				[
					'user_id' => $user_id,
					'plan_id' => $plan_id,
					'source'  => $source,
				]
			);

			if ( ! is_wp_error( $membership ) ) {
				// Update status.
				$membership->update_status( $user_status, 'Membership created via CSV import.' );
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
		if ( ! function_exists( 'wc_memberships_get_user_active_memberships' ) ) {
			return [];
		}

		$memberships = wc_memberships_get_user_active_memberships( $user_id );
		$active_plans = [];

		foreach ( $memberships as $membership ) {
			$active_plans[] = absint( $membership->get_plan_id() );
		}

		return $active_plans;
	}

	/**
	 * Get all memberships for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array Array of membership plan IDs.
	 */
	public static function get_user_memberships( $user_id ) {
		if ( ! function_exists( 'wc_memberships_get_user_memberships' ) ) {
			return [];
		}

		$memberships = wc_memberships_get_user_memberships( $user_id );
		$plans       = [];

		foreach ( $memberships as $membership ) {
			$plans[] = absint( $membership->get_plan_id() );
		}

		return $plans;
	}

	/**
	 * Get user's export status based on membership access.
	 *
	 * @param int $user_id User ID.
	 * @return string Status ('active' if has access, 'inactive' if no access).
	 */
	public static function get_user_export_status( $user_id ) {
		return self::has_any_membership_access( $user_id );
	}
}
