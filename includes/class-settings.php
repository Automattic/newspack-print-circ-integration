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
	 * Runs the initialization.
	 */
	public static function init() {
		// Setup Hooks & Filters.
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
	}

	/**
	 * Register settings using WordPress Settings API.
	 */
	public static function register_settings() {
		register_setting( self::SETTINGS_OPTION, self::SETTINGS_OPTION );

		add_settings_section(
			'newspack_print_general_settings',
			__( 'General Settings', 'newspack-print' ),
			null,
			self::SETTINGS_OPTION
		);

		add_settings_field(
			'csv_import_path',
			__( 'CSV Import Path', 'newspack-print' ),
			[ __CLASS__, 'render_text_field' ],
			self::SETTINGS_OPTION,
			'newspack_print_general_settings',
			[
				'label_for' => 'csv_import_path',
				'description' => __( 'Path to the CSV file for import.', 'newspack-print' ),
			]
		);

		add_settings_field(
			'csv_mapping',
			__( 'CSV Mapping', 'newspack-print' ),
			[ __CLASS__, 'render_textarea_field' ],
			self::SETTINGS_OPTION,
			'newspack_print_general_settings',
			[
				'label_for' => 'csv_mapping',
				'description' => __( 'Field mapping in JSON format. Make sure to include "circulation_id" as the unique identifier for the mapping', 'newspack-print' ),
			]
		);
	}

	/**
	 * Register the settings page.
	 */
	public static function register_settings_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Newspack Print Settings', 'newspack-print' ),
			__( 'Newspack Print', 'newspack-print' ),
			'manage_options',
			self::SETTINGS_OPTION,
			[ __CLASS__, 'render_settings_page' ]
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
	 */
	public static function render_text_field( $args ) {
		$options = get_option( self::SETTINGS_OPTION );
		$value = isset( $options[ $args['label_for'] ] ) ? esc_attr( $options[ $args['label_for'] ] ) : '';
		?>
		<input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( self::SETTINGS_OPTION . '[' . $args['label_for'] . ']' ); ?>" value="<?php echo $value; ?>" class="regular-text">
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}

	/**
	 * Render a textarea field.
	 */
	public static function render_textarea_field( $args ) {
		$options = get_option( self::SETTINGS_OPTION );
		$value = isset( $options[ $args['label_for'] ] ) ? esc_textarea( $options[ $args['label_for'] ] ) : '';
		?>
		<textarea id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( self::SETTINGS_OPTION . '[' . $args['label_for'] . ']' ); ?>" class="large-text" rows="5"><?php echo $value; ?></textarea>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}
}
