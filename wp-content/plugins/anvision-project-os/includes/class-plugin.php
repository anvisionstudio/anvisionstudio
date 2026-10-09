<?php
/**
 * WordPress plugin bootstrap (Infrastructure).
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\WordPress;

use AnvisionStudio\ProjectOS\Infrastructure\Http\RestRegistrar;
use AnvisionStudio\ProjectOS\Infrastructure\Persistence\MigrationRunner;
use AnvisionStudio\ProjectOS\Infrastructure\Persistence\TableNames;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton wiring hooks, migrations, and REST.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get shared plugin instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {}

	/**
	 * Register WordPress hooks.
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register_capabilities' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		$this->maybe_run_migrations();
	}

	/**
	 * Activation: capabilities + schema migrations.
	 */
	public function activate(): void {
		$this->register_capabilities();
		$this->maybe_run_migrations();
		flush_rewrite_rules();
	}

	/**
	 * Grant manage_avs_projects to administrators.
	 */
	public function register_capabilities(): void {
		$role = get_role( 'administrator' );
		if ( $role && ! $role->has_cap( 'manage_avs_projects' ) ) {
			$role->add_cap( 'manage_avs_projects' );
		}
	}

	/**
	 * Register REST routes after ensuring schema is current.
	 */
	public function register_rest_routes(): void {
		$this->maybe_run_migrations();
		( new RestRegistrar() )->register();
	}

	/**
	 * Apply any pending database migrations.
	 */
	private function maybe_run_migrations(): void {
		global $wpdb;
		$runner = new MigrationRunner( $wpdb, new TableNames( $wpdb ) );
		$runner->run_pending();
	}
}
