<?php
/**
 * Align schema to authoritative v0.1 Phase 1 baseline.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Persistence\Migrations;

use AnvisionStudio\ProjectOS\Infrastructure\Persistence\MigrationInterface;
use AnvisionStudio\ProjectOS\Infrastructure\Persistence\TableNames;

/**
 * Drops out-of-scope reconstruction tables and ensures Phase 1 tables exist.
 */
final class Migration_202510090002_Phase1Schema implements MigrationInterface {

	public function __construct(
		private \wpdb $wpdb,
		private TableNames $tables
	) {}

	public function version(): string {
		return '202510090002';
	}

	public function up(): void {
		// Remove tables from the earlier over-scoped reconstruction (not in v0.1).
		$obsolete = array(
			$this->wpdb->prefix . 'avs_quotation_versions',
			$this->wpdb->prefix . 'avs_quotation_audit_log',
		);
		foreach ( $obsolete as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		// Re-apply Phase 1 schema (dbDelta is additive/safe).
		( new Migration_202510090001_CoreSchema( $this->wpdb, $this->tables ) )->up();

		// Drop legacy columns from quotations if the old reconstruction created them.
		$quotations = $this->tables->quotations();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cols = $this->wpdb->get_col( "DESCRIBE {$quotations}", 0 );
		if ( is_array( $cols ) && in_array( 'review_status', $cols, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->query( "DROP TABLE IF EXISTS {$quotations}" );
			( new Migration_202510090001_CoreSchema( $this->wpdb, $this->tables ) )->up();
		}
	}
}
