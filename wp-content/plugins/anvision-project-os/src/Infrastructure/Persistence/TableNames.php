<?php
/**
 * Database table names.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Persistence;

/**
 * Resolves prefixed table names.
 */
final class TableNames {

	public function __construct( private \wpdb $wpdb ) {}

	public function schema_migrations(): string {
		return $this->wpdb->prefix . 'avs_schema_migrations';
	}

	public function idempotency_keys(): string {
		return $this->wpdb->prefix . 'avs_idempotency_keys';
	}

	public function quotations(): string {
		return $this->wpdb->prefix . 'avs_quotations';
	}

	public function quotation_versions(): string {
		return $this->wpdb->prefix . 'avs_quotation_versions';
	}

	public function quotation_audit_log(): string {
		return $this->wpdb->prefix . 'avs_quotation_audit_log';
	}
}
