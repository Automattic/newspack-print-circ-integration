<?php
/**
 * Settings Menu
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

/**
 * Add Settings to configure the integration.
 *
 * @since 1.0
 */
class Settings {

	/**
	 * Option name for the settings.
	 */
	const SETTINGS_OPTION = 'newspack_print_circ_settings';

	/**
	 * TBD. Import path for the CSV file.
	 */
	const CSV_IMPORT_PATH_OPTION = 'csv_import_path';

	/**
	 * CSV Mapping settings.
	 */
	const CSV_MAPPING_OPTION = 'csv_mapping';

	/**
	 * Default role to be granted to the imported users.
	 */
	const DEFAULT_ROLES_OPTION = 'default_roles';

	/**
	 * TBD. Default subscriptions to be granted to the imported users.
	 */
	const DEFAULT_SUBSCRIPTION_PRODUCTS_OPTION = 'default_subscription_products';

	/**
	 * TBD. Default memberships to be granted to the imported users.
	 */
	const DEFAULT_MEMBERSHIPS_OPTION = 'new_users_granted_membership_plan_id';

	/**
	 * TBD. Users with these plans will be exported to the Print Circulation System.
	 */
	const ALLOWED_MEMBERSHIPS_OPTION = 'allowed_membership_plan_ids';

	/**
	 * Import and Export Transformations logic to be applied to the data.
	 */
	const IMPORT_TRANSFORMATIONS_OPTION = 'import_transformations';
	const EXPORT_TRANSFORMATIONS_OPTION = 'export_transformations';

	/**
	 * Cron schedule for the Print Circulation System.
	 */
	const SYNC_CRON_SCHEDULE = 'newspack_print_circ_sync_cron_schedule';

	/**
	 * Is logging enabled?
	 */
	const LOGGING_ENABLED_OPTION = 'logging_enabled';

	/**
	 * Runs the initialization.
	 */
	public static function init() {
		// Setup Hooks & Filters.
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
		add_action( 'admin_menu', [ __CLASS__, 'register_logs_page' ] );
	}

	/**
	 * Register settings using WordPress Settings API.
	 */
	public static function register_settings() {
		register_setting(
			self::SETTINGS_OPTION,
			self::SETTINGS_OPTION,
			[
				'type'              => 'array',
				'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ],
			]
		);

		add_settings_section(
			'newspack_print_general_settings',
			__( 'General Settings', 'newspack-print' ),
			null,
			self::SETTINGS_OPTION
		);

		add_settings_field(
			self::CSV_IMPORT_PATH_OPTION,
			__( 'CSV Import Path', 'newspack-print' ),
			[ __CLASS__, 'render_text_field' ],
			self::SETTINGS_OPTION,
			'newspack_print_general_settings',
			[
				'label_for'   => self::CSV_IMPORT_PATH_OPTION,
				'description' => __( 'Path to the CSV file for import.', 'newspack-print' ),
			]
		);

		add_settings_field(
			self::CSV_MAPPING_OPTION,
			__( 'CSV Mapping', 'newspack-print' ),
			[ __CLASS__, 'render_textarea_field' ],
			self::SETTINGS_OPTION,
			'newspack_print_general_settings',
			[
				'label_for'   => self::CSV_MAPPING_OPTION,
				'description' => __( 'Field mapping in JSON format. Make sure to include "circulation_id" as the unique identifier for the mapping', 'newspack-print' ),
			]
		);

		add_settings_field(
			self::IMPORT_TRANSFORMATIONS_OPTION,
			__( 'Import Transformations', 'newspack-print' ),
			[ __CLASS__, 'render_textarea_field' ],
			self::SETTINGS_OPTION,
			'newspack_print_general_settings',
			[
				'label_for'   => self::IMPORT_TRANSFORMATIONS_OPTION,
				'description' => __( 'Transformations to apply to the imported data in JSON format.', 'newspack-print' ),
			]
		);

		add_settings_field(
			self::EXPORT_TRANSFORMATIONS_OPTION,
			__( 'Export Transformations', 'newspack-print' ),
			[ __CLASS__, 'render_textarea_field' ],
			self::SETTINGS_OPTION,
			'newspack_print_general_settings',
			[
				'label_for'   => self::EXPORT_TRANSFORMATIONS_OPTION,
				'description' => __( 'Transformations to apply to the exported data in JSON format.', 'newspack-print' ),
			]
		);

		add_settings_field(
			self::SYNC_CRON_SCHEDULE,
			__( 'Users Sync Schedule', 'newspack-print' ),
			[ __CLASS__, 'render_time_selector_field' ],
			self::SETTINGS_OPTION,
			'newspack_print_general_settings',
			[
				'label_for'   => self::SYNC_CRON_SCHEDULE,
				'description' => __( 'Schedule for the Print Circulation System users sync.', 'newspack-print' ),
			]
		);

		add_settings_field(
			self::LOGGING_ENABLED_OPTION,
			__( 'Enable Logging?', 'newspack-print' ),
			[ __CLASS__, 'render_checkbox_field' ],
			self::SETTINGS_OPTION,
			'newspack_print_general_settings',
			[
				'label_for'   => self::LOGGING_ENABLED_OPTION,
				'description' => __( 'Enable logging for the integration.', 'newspack-print' ),
			]
		);

		// Access Criteria Section.
		add_settings_section(
			'newspack_print_access_criteria',
			__( 'Access Criteria', 'newspack-print' ),
			null,
			self::SETTINGS_OPTION
		);

		// Allowed Roles.
		add_settings_field(
			self::DEFAULT_ROLES_OPTION,
			__( 'Default User Roles', 'newspack-print' ),
			[ __CLASS__, 'render_multiselect_field' ],
			self::SETTINGS_OPTION,
			'newspack_print_access_criteria',
			[
				'label_for'   => self::DEFAULT_ROLES_OPTION,
				'description' => __( 'Select roles to be granted to the imported users.', 'newspack-print' ),
				'options'     => self::get_roles_options(),
			]
		);

		// Allowed Subscriptions.
		if ( class_exists( 'WC_Subscriptions' ) ) {
			add_settings_field(
				self::DEFAULT_SUBSCRIPTION_PRODUCTS_OPTION,
				__( 'Default user subscriptions', 'newspack-print' ),
				[ __CLASS__, 'render_multiselect_field' ],
				self::SETTINGS_OPTION,
				'newspack_print_access_criteria',
				[
					'label_for'   => self::DEFAULT_SUBSCRIPTION_PRODUCTS_OPTION,
					'description' => __( 'Select WooCommerce Subscriptions to be granted to the imported users.', 'newspack-print' ),
					'options'     => self::get_woocommerce_subscription_products_options(),
				]
			);
		}

		// Allowed Memberships.
		if ( class_exists( 'WC_Memberships' ) ) {
			add_settings_field(
				self::DEFAULT_MEMBERSHIPS_OPTION,
				__( 'Default user memberships', 'newspack-print' ),
				[ __CLASS__, 'render_multiselect_field' ],
				self::SETTINGS_OPTION,
				'newspack_print_access_criteria',
				[
					'label_for'   => self::DEFAULT_MEMBERSHIPS_OPTION,
					'description' => __( 'Select WooCommerce Memberships to be granted to the imported users.', 'newspack-print' ),
					'options'     => self::get_woocommerce_memberships_options(),
				]
			);
		}
	}

	/**
	 * Register the settings page.
	 */
	public static function register_settings_page() {
		add_menu_page(
			__( 'Newspack Print', 'newspack-print' ),
			__( 'Newspack Print Settings', 'newspack-print' ),
			'manage_options',
			self::SETTINGS_OPTION,
			[ __CLASS__, 'render_settings_page' ],
			'dashicons-admin-generic',
		);
	}

	/**
	 * Render the settings page.
	 */
	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Newspack Print Settings', 'newspack-print' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::SETTINGS_OPTION );
				do_settings_sections( self::SETTINGS_OPTION );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a text field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_text_field( $args ) {
		$options = get_option( self::SETTINGS_OPTION );
		$value   = isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : '';
		?>
		<input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( self::SETTINGS_OPTION . '[' . $args['label_for'] . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}

	/**
	 * Render a textarea field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_textarea_field( $args ) {
		$options = get_option( self::SETTINGS_OPTION, [] );
		$value   = isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : '';
		?>
		<textarea id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( self::SETTINGS_OPTION . '[' . $args['label_for'] . ']' ); ?>" class="large-text" rows="5"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}

	/**
	 * Render a multiselect field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_multiselect_field( $args ) {
		$options = get_option( self::SETTINGS_OPTION );
		$values  = isset( $options[ $args['label_for'] ] ) ? (array) $options[ $args['label_for'] ] : [];
		?>
		<select id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( self::SETTINGS_OPTION . '[' . $args['label_for'] . '][]' ); ?>" multiple class="regular-text">
			<?php foreach ( $args['options'] as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( in_array( $key, $values, false ) ); // phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}

	/**
	 * Render a time selector field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_time_selector_field( $args ) {
		$options = get_option( self::SETTINGS_OPTION );
		$value   = isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : '';
		?>
		<input type="time" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( self::SETTINGS_OPTION . '[' . $args['label_for'] . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_checkbox_field( $args ) {
		$options = get_option( self::SETTINGS_OPTION );
		$value   = isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : 0;
		?>
		<input type="checkbox" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( self::SETTINGS_OPTION . '[' . $args['label_for'] . ']' ); ?>" value="1" <?php checked( $value, 1 ); ?>>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Input settings.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( $input ) {
		$sanitized = [];

		// Sanitize CSV import path.
		if ( isset( $input[ self::CSV_IMPORT_PATH_OPTION ] ) ) {
			$sanitized[ self::CSV_IMPORT_PATH_OPTION ] = sanitize_url( $input[ self::CSV_IMPORT_PATH_OPTION ] );
		}

		// Sanitize CSV mapping.
		if ( isset( $input[ self::CSV_MAPPING_OPTION ] ) ) {
			$sanitized[ self::CSV_MAPPING_OPTION ] = sanitize_text_field( $input[ self::CSV_MAPPING_OPTION ] );
		}

		// Sanitize allowed roles.
		if ( isset( $input[ self::DEFAULT_ROLES_OPTION ] ) ) {
			$sanitized[ self::DEFAULT_ROLES_OPTION ] = array_map( 'sanitize_text_field', (array) $input[ self::DEFAULT_ROLES_OPTION ] );
		}

		// Sanitize allowed subscriptions.
		if ( isset( $input[ self::DEFAULT_SUBSCRIPTION_PRODUCTS_OPTION ] ) ) {
			$sanitized[ self::DEFAULT_SUBSCRIPTION_PRODUCTS_OPTION ] = array_map( 'sanitize_text_field', (array) $input[ self::DEFAULT_SUBSCRIPTION_PRODUCTS_OPTION ] );
		}

		// Sanitize allowed memberships.
		if ( isset( $input[ self::DEFAULT_MEMBERSHIPS_OPTION ] ) ) {
			$sanitized[ self::DEFAULT_MEMBERSHIPS_OPTION ] = array_map( 'sanitize_text_field', (array) $input[ self::DEFAULT_MEMBERSHIPS_OPTION ] );
		}

		// Sanitize import transformations.
		if ( isset( $input[ self::IMPORT_TRANSFORMATIONS_OPTION ] ) ) {
			// Check if the input is a valid callable.
			$import_transformations = $input[ self::IMPORT_TRANSFORMATIONS_OPTION ];
			/**
			 * TODO: eval is a huge security risk! need to find a better way to do this.
			 */
			$import_transformations = eval( 'return ' . $import_transformations . ';' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged

			if ( is_callable( $import_transformations ) ) {
				$sanitized[ self::IMPORT_TRANSFORMATIONS_OPTION ] = sanitize_text_field( $input[ self::IMPORT_TRANSFORMATIONS_OPTION ] );
			}
		}

		// Sanitize cron schedule.
		if ( isset( $input[ self::SYNC_CRON_SCHEDULE ] ) ) {
			$sanitized[ self::SYNC_CRON_SCHEDULE ] = sanitize_text_field( $input[ self::SYNC_CRON_SCHEDULE ] );
		}

		// Sanitize logging enabled.
		if ( isset( $input[ self::LOGGING_ENABLED_OPTION ] ) ) {
			$sanitized[ self::LOGGING_ENABLED_OPTION ] = (bool) $input[ self::LOGGING_ENABLED_OPTION ];
		}

		return $sanitized;
	}

	/**
	 * Get WordPress roles.
	 */
	private static function get_roles_options() {
		$roles = wp_roles()->roles;
		$options = [];
		foreach ( $roles as $key => $role ) {
			$options[ $key ] = $role['name'];
		}
		return $options;
	}

	/**
	 * Get WooCommerce Subscriptions.
	 */
	private static function get_woocommerce_subscription_products_options() {
		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			return [];
		}

		$subscriptions = wc_get_products(
			[
				'type'  => [ 'subscription', 'variable-subscription' ],
				'limit' => -1,
			]
		);

		$options = [];
		foreach ( $subscriptions as $subscription ) {
			$options[ $subscription->get_id() ] = $subscription->get_name();
		}
		return $options;
	}

	/**
	 * Get WooCommerce Memberships.
	 */
	private static function get_woocommerce_memberships_options() {
		if ( ! class_exists( 'WC_Memberships' ) ) {
			return [];
		}

		$memberships = wc_memberships_get_membership_plans();
		$options = [];
		foreach ( $memberships as $membership ) {
			$options[ $membership->get_id() ] = $membership->get_name();
		}
		return $options;
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $option Option name.
	 * @return mixed|null Option value.
	 */
	public static function get_setting( $option ) {
		$options = get_option( self::SETTINGS_OPTION );

		if ( ! isset( $options[ $option ] ) ) {
			return null;
		}

		return $options[ $option ];
	}

	/**
	 * Register the logs sub-page.
	 */
	public static function register_logs_page() {
		add_submenu_page(
			self::SETTINGS_OPTION,
			__( 'Newspack Print Logs', 'newspack-print' ),
			__( 'Logs', 'newspack-print' ),
			'manage_options',
			'newspack_print_logs',
			[ __CLASS__, 'render_logs_page' ]
		);
	}

	/**
	 * Render the logs page.
	 */
	public static function render_logs_page() {
		$logs = Logger::get_logs();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Newspack Print Logs', 'newspack-print' ); ?></h1>
			<table class="widefat fixed" cellspacing="0">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'newspack-print' ); ?></th>
						<th><?php esc_html_e( 'Message', 'newspack-print' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr>
							<td colspan="2"><?php esc_html_e( 'No logs available.', 'newspack-print' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log['time'] ); ?></td>
								<td><?php echo esc_html( $log['message'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<div class="tablenav bottom">
				<form method="post" action="">
					<?php wp_nonce_field( 'clear_logs_action', 'clear_logs_nonce' ); ?>
					<input type="submit" name="clear_logs" class="button button-secondary" value="<?php esc_attr_e( 'Clear Logs', 'newspack-print' ); ?>">
				</form>
			</div>
		</div>
		<?php

		// Handle log clearing.
		if ( isset( $_POST['clear_logs'] ) && check_admin_referer( 'clear_logs_action', 'clear_logs_nonce' ) ) {
			Logger::clear_logs();
			wp_safe_redirect( admin_url( 'admin.php?page=newspack_print_logs' ) );
			exit;
		}
	}
}
