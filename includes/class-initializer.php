<?php
/**
 * Newspack Print Integration Initializer.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

use WP_CLI;
use Newspack\PrintCirculationIntegration\Import as Newspack_Print_Import;

/**
 * Class to handle the plugin initialization
 */
class Initializer {

	/**
	 * Import module.
	 *
	 * @var Newspack_Print_Import
	 */
	protected $import_module;

	/**
	 * Cron job hook.
	 */
	const CRON_JOB_HOOK = 'newspack_print_circ_sync';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Setup Hooks & Filters.
		add_action( 'admin_notices', array( __CLASS__, 'show_admin_notice__error' ) );
		add_action( 'cli_init', array( __CLASS__, 'register_cli_commands' ) );

		/**
		 * Schedule the cron job.
		 */
		// add_action( 'update_option_' . Settings::SETTINGS_OPTION, array( __CLASS__, 'schedule_cron_jobs' ), 10, 2 );
		// add_action( self::CRON_JOB_HOOK, array( $this, 'users_import_sync' ) );

		/**
		 * TODO: To be scheduled as a cron job. Not on every page load.
		 */
		add_action( 'plugins_loaded', array( $this, 'initialize_import' ) );

		Settings::init();
	}

	/**
	 * Initialize the import process.
	 * Background processes needs to be initialized early on.
	 * TODO: Do this once as a cron job.
	 */
	public function initialize_import() {
		$this->import_module = new Newspack_Print_Import();
	}

	/**
	 * Sync - Import users from CSV file.
	 * This fetches the CSV file and fires background process to import users.
	 */
	public function users_import_sync() {
		// Fetch the CSV file.
		$fetch_csv_status = $this->import_module->fetch_csv_file();

		if ( is_wp_error( $fetch_csv_status ) ) {
			// Log error.
			Logger::add_log( 'Error fetching CSV file: ' . $fetch_csv_status->get_error_message() );
			return;
		}

		Logger::add_log( 'CSV file fetched successfully.' );
		Logger::add_log( 'Starting user import...' );

		// Define the batch size.
		$batch_size = defined( 'NEWSPACK_PRINT_CIRC_BATCH_SIZE' ) ? NEWSPACK_PRINT_CIRC_BATCH_SIZE : 20;

		/**
		 * TODO: This will be replaced with import_users( $batch_size ) function once the import functionality is validated.
		 */
		// Import users.
		$import_result = $this->import_module->test_import_users();

		if ( is_wp_error( $import_result ) ) {
			// Log error.
			Logger::add_log( 'Error importing users: ' . $import_result->get_error_message() );
			return;
		}

		// Cleanup.
		$this->import_module->clean_up();
	}

	/**
	 * Check and displays plugin specific notices when required.
	 *
	 * @return bool Return false on error.
	 */
	public static function has_valid_dependencies() {
		if ( ! DependencyChecker::is_wc_installed()
			|| ! DependencyChecker::is_wc_memberships_installed()
			|| ! DependencyChecker::is_wc_subscriptions_installed()
		) {
			return false;
		}
		return true;
	}

	/**
	 * Displays admin notice summarizing error.
	 */
	public static function show_admin_notice__error() {
		$plugin_notice    = '';
		$inactive_plugins = array();
		$allowed_html     = array(
			'a'      => array(
				'href' => array(),
			),
			'strong' => array(),
		);

		if ( ! DependencyChecker::is_wc_installed() ) {
			$inactive_plugins[] = 'WooCommerce';
		}

		if ( ! DependencyChecker::is_wc_memberships_installed() ) {
			$inactive_plugins[] = 'WooCommerce Memberships';
		}

		if ( ! DependencyChecker::is_wc_subscriptions_installed() ) {
			$inactive_plugins[] = 'WooCommerce Subscriptions';
		}

		if ( ! empty( $inactive_plugins ) ) {
			// Based on the number of inactive plugins, display the appropriate message.
			if ( 1 === count( $inactive_plugins ) ) {
				$plugin_notice = sprintf(
					/* translators: %s: Plugin name. */
					esc_html__( 'Newspack Print Circulation Integration requires %s to be installed and activated.', 'newspack-print' ),
					'<strong>' . esc_html( $inactive_plugins[0] ) . '</strong>'
				);
			} else {
				$plugin_notice = sprintf(
					/* translators: %s: Plugin names. */
					esc_html__( 'Newspack Print Circulation Integration requires %s to be installed and activated.', 'newspack-print' ),
					'<strong>' . esc_html( implode( ', ', $inactive_plugins ) ) . '</strong>'
				);
			}
		}

		if ( ! empty( $plugin_notice ) ) {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					echo wp_kses( $plugin_notice, $allowed_html );
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Schedule cron jobs.
	 *
	 * @param array $old_value Old settings value.
	 * @param array $value New settings value.
	 */
	public static function schedule_cron_jobs( $old_value, $value ) {
		// Check if there is a change in the settings.
		$old_cron_time = isset( $old_value[ Settings::SYNC_CRON_SCHEDULE ] ) ? $old_value[ Settings::SYNC_CRON_SCHEDULE ] : '';
		$new_cron_time = isset( $value[ Settings::SYNC_CRON_SCHEDULE ] ) ? $value[ Settings::SYNC_CRON_SCHEDULE ] : '';

		// Clear the cron job if there is no schedule.
		if ( empty( $new_cron_time ) && wp_next_scheduled( self::CRON_JOB_HOOK ) ) {
			wp_clear_scheduled_hook( self::CRON_JOB_HOOK );
			return;
		}

		// Schedule the cron job if the time has changed.
		if ( $old_cron_time !== $new_cron_time ) {
			$cron_job_time = strtotime( $new_cron_time );

			// Schedule the new cron job.
			wp_clear_scheduled_hook( self::CRON_JOB_HOOK );
			wp_schedule_event( $cron_job_time, 'daily', self::CRON_JOB_HOOK );
		}
	}

	/**
	 * Register WP-CLI commands.
	 */
	public static function register_cli_commands() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		// Register the CLI command.
		require_once __DIR__ . '/wp-cli/class-import.php';

		WP_CLI::add_command( 'newspack-print import-users', [ 'Newspack\PrintCirculationIntegration\CLI\Import', 'import_users' ] );
	}

	/**
	 * Deactivate the plugin.
	 */
	public static function deactivate_plugin() {
		// Clear the settings.
		// delete_option( Settings::SETTINGS_OPTION );.

		// Clear the cron job.
		wp_clear_scheduled_hook( self::CRON_JOB_HOOK );
	}
}
