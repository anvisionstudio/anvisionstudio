<?php
/**
 * Service catalog MySQL repository.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Persistence;

use AnvisionStudio\ProjectOS\Application\Contracts\ServiceCatalogRepositoryInterface;
use AnvisionStudio\ProjectOS\Domain\Catalog\ServiceItem;

/**
 * Read-only catalog persistence.
 */
final class ServiceCatalogRepository implements ServiceCatalogRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
		private TableNames $tables
	) {}

	public function list_active(): array {
		$table = $this->tables->services();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $this->wpdb->get_results(
			"SELECT * FROM {$table} WHERE active = 1 ORDER BY code ASC",
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return array();
		}
		return array_map( array( $this, 'map_row' ), $rows );
	}

	public function find_by_id( int $service_id ): ?ServiceItem {
		$table = $this->tables->services();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $service_id ),
			ARRAY_A
		);
		return is_array( $row ) ? $this->map_row( $row ) : null;
	}

	public function find_by_code( string $code ): ?ServiceItem {
		$table = $this->tables->services();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$table} WHERE code = %s", $code ),
			ARRAY_A
		);
		return is_array( $row ) ? $this->map_row( $row ) : null;
	}

	/**
	 * @param array<string, mixed> $row DB row.
	 */
	private function map_row( array $row ): ServiceItem {
		return new ServiceItem(
			(int) $row['id'],
			(string) $row['code'],
			(string) $row['name'],
			(string) $row['description'],
			(int) $row['unit_price_twd'],
			(bool) (int) $row['active']
		);
	}
}
