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

	public function services(): string {
		return $this->wpdb->prefix . 'avs_services';
	}

	public function quotations(): string {
		return $this->wpdb->prefix . 'avs_quotations';
	}

	public function quotation_items(): string {
		return $this->wpdb->prefix . 'avs_quotation_items';
	}
}
