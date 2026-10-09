<?php
/**
 * Phase 1 core schema.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Persistence\Migrations;

use AnvisionStudio\ProjectOS\Infrastructure\Persistence\MigrationInterface;
use AnvisionStudio\ProjectOS\Infrastructure\Persistence\TableNames;

/**
 * Idempotency, service catalog, draft quotations.
 */
final class Migration_202510090001_CoreSchema implements MigrationInterface {

	public function __construct(
		private \wpdb $wpdb,
		private TableNames $tables
	) {}

	public function version(): string {
		return '202510090001';
	}

	public function up(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $this->wpdb->get_charset_collate();

		$idempotency = $this->tables->idempotency_keys();
		dbDelta(
			"CREATE TABLE {$idempotency} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				idempotency_key varchar(128) NOT NULL,
				request_hash char(64) NOT NULL,
				route varchar(255) NOT NULL,
				method varchar(10) NOT NULL,
				response_code smallint unsigned NOT NULL,
				response_body longtext NOT NULL,
				expires_at datetime NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY idempotency_key (idempotency_key),
				KEY expires_at (expires_at)
			) {$charset};"
		);

		$services = $this->tables->services();
		dbDelta(
			"CREATE TABLE {$services} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				code varchar(64) NOT NULL,
				name varchar(255) NOT NULL,
				description text NOT NULL,
				unit_price_twd int NOT NULL,
				active tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY code (code),
				KEY active (active)
			) {$charset};"
		);

		$quotations = $this->tables->quotations();
		dbDelta(
			"CREATE TABLE {$quotations} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				title varchar(255) NOT NULL,
				status varchar(32) NOT NULL DEFAULT 'draft',
				currency char(3) NOT NULL DEFAULT 'TWD',
				subtotal_twd int NOT NULL,
				total_twd int NOT NULL,
				created_by bigint(20) unsigned NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY status (status)
			) {$charset};"
		);

		$items = $this->tables->quotation_items();
		dbDelta(
			"CREATE TABLE {$items} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				quotation_id bigint(20) unsigned NOT NULL,
				service_id bigint(20) unsigned DEFAULT NULL,
				service_code varchar(64) NOT NULL DEFAULT '',
				description varchar(255) NOT NULL,
				quantity int unsigned NOT NULL,
				unit_price_twd int NOT NULL,
				line_total_twd int NOT NULL,
				PRIMARY KEY  (id),
				KEY quotation_id (quotation_id)
			) {$charset};"
		);

		$this->seed_catalog_if_empty();
	}

	private function seed_catalog_if_empty(): void {
		$table = $this->tables->services();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $count > 0 ) {
			return;
		}

		$now  = current_time( 'mysql', true );
		$rows = array(
			array( 'WEB-UX', 'Website UX', 'UX discovery and wireframes', 50000 ),
			array( 'WEB-DEV', 'Website Build', 'Front-end implementation', 120000 ),
			array( 'HOST-1Y', 'Hosting 1Y', 'Managed hosting (annual)', 12000 ),
		);
		foreach ( $rows as $row ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$this->wpdb->insert(
				$table,
				array(
					'code'           => $row[0],
					'name'           => $row[1],
					'description'    => $row[2],
					'unit_price_twd' => $row[3],
					'active'         => 1,
					'created_at'     => $now,
				),
				array( '%s', '%s', '%s', '%d', '%d', '%s' )
			);
		}
	}
}
