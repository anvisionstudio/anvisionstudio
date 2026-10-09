<?php
/**
 * Core Project OS schema.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Persistence\Migrations;

use AnvisionStudio\ProjectOS\Infrastructure\Persistence\MigrationInterface;
use AnvisionStudio\ProjectOS\Infrastructure\Persistence\TableNames;

/**
 * Initial tables for idempotency, quotations, audit log.
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

		$quotations = $this->tables->quotations();
		dbDelta(
			"CREATE TABLE {$quotations} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				title varchar(255) NOT NULL,
				review_status varchar(32) NOT NULL DEFAULT 'draft',
				current_version_id bigint(20) unsigned DEFAULT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY review_status (review_status)
			) {$charset};"
		);

		$versions = $this->tables->quotation_versions();
		dbDelta(
			"CREATE TABLE {$versions} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				quotation_id bigint(20) unsigned NOT NULL,
				version_number int unsigned NOT NULL,
				is_snapshot tinyint(1) NOT NULL DEFAULT 0,
				snapshot_of_version_id bigint(20) unsigned DEFAULT NULL,
				currency char(3) NOT NULL,
				subtotal_minor bigint(20) NOT NULL,
				tax_minor bigint(20) NOT NULL,
				total_minor bigint(20) NOT NULL,
				line_items_json longtext NOT NULL,
				created_by bigint(20) unsigned NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY quotation_version (quotation_id, version_number),
				KEY quotation_id (quotation_id),
				KEY is_snapshot (is_snapshot)
			) {$charset};"
		);

		$audit = $this->tables->quotation_audit_log();
		dbDelta(
			"CREATE TABLE {$audit} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				quotation_id bigint(20) unsigned NOT NULL,
				from_status varchar(32) NOT NULL,
				to_status varchar(32) NOT NULL,
				actor_user_id bigint(20) unsigned NOT NULL,
				note text NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY quotation_id (quotation_id)
			) {$charset};"
		);
	}
}
