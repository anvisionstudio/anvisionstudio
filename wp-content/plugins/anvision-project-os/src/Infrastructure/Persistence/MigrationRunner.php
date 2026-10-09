<?php
/**
 * Runs pending database migrations.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Persistence;

use AnvisionStudio\ProjectOS\Infrastructure\Persistence\Migrations\Migration_202510090001_CoreSchema;

/**
 * Applies lexicographically ordered migrations once.
 */
final class MigrationRunner {

	/**
	 * @var list<class-string<MigrationInterface>>
	 */
	private const MIGRATIONS = array(
		Migration_202510090001_CoreSchema::class,
	);

	public function __construct(
		private \wpdb $wpdb,
		private TableNames $tables
	) {}

	public function run_pending(): void {
		$this->ensure_migrations_table();

		$applied = $this->applied_versions();
		foreach ( self::MIGRATIONS as $class ) {
			/** @var MigrationInterface $migration */
			$migration = new $class( $this->wpdb, $this->tables );
			$version   = $migration->version();
			if ( in_array( $version, $applied, true ) ) {
				continue;
			}
			$migration->up();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$this->wpdb->insert(
				$this->tables->schema_migrations(),
				array(
					'version'    => $version,
					'applied_at' => current_time( 'mysql', true ),
				),
				array( '%s', '%s' )
			);
		}
	}

	private function ensure_migrations_table(): void {
		$table   = $this->tables->schema_migrations();
		$charset = $this->wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			version varchar(32) NOT NULL,
			applied_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY version (version)
		) {$charset};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * @return list<string>
	 */
	private function applied_versions(): array {
		$table = $this->tables->schema_migrations();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $this->wpdb->get_col( "SELECT version FROM {$table} ORDER BY version ASC" );
		return is_array( $rows ) ? array_map( 'strval', $rows ) : array();
	}
}
