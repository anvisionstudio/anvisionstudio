<?php
/**
 * Quotation MySQL repository.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Persistence;

use AnvisionStudio\ProjectOS\Application\Contracts\QuotationRepositoryInterface;
use AnvisionStudio\ProjectOS\Domain\Money\LineItem;
use AnvisionStudio\ProjectOS\Domain\Money\MoneyTotals;

/**
 * Persists draft quotations with transactional multi-table writes.
 */
final class QuotationRepository implements QuotationRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
		private TableNames $tables
	) {}

	/**
	 * @param LineItem[] $items Items.
	 */
	public function create_draft(
		string $title,
		array $items,
		MoneyTotals $totals,
		int $actor_user_id
	): array {
		$now = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$this->wpdb->query( 'START TRANSACTION' );

		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $this->wpdb->insert(
				$this->tables->quotations(),
				array(
					'title'        => $title,
					'status'       => 'draft',
					'currency'     => 'TWD',
					'subtotal_twd' => $totals->subtotal_twd,
					'total_twd'    => $totals->total_twd,
					'created_by'   => $actor_user_id,
					'created_at'   => $now,
					'updated_at'   => $now,
				),
				array( '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
			);
			if ( false === $inserted ) {
				throw new \RuntimeException( 'Failed to insert quotation.' );
			}

			$quotation_id = (int) $this->wpdb->insert_id;

			foreach ( $items as $item ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$ok = $this->wpdb->insert(
					$this->tables->quotation_items(),
					array(
						'quotation_id'   => $quotation_id,
						'service_id'     => $item->service_id,
						'service_code'   => $item->service_code,
						'description'    => $item->description,
						'quantity'       => $item->quantity,
						'unit_price_twd' => $item->unit_price_twd,
						'line_total_twd' => $item->line_total_twd(),
					),
					array( '%d', '%d', '%s', '%s', '%d', '%d', '%d' )
				);
				if ( false === $ok ) {
					throw new \RuntimeException( 'Failed to insert quotation line.' );
				}
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$this->wpdb->query( 'COMMIT' );
		} catch ( \Throwable $e ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$this->wpdb->query( 'ROLLBACK' );
			throw $e;
		}

		$row = $this->get( $quotation_id );
		if ( null === $row ) {
			throw new \RuntimeException( 'Quotation missing after insert.' );
		}
		return $row;
	}

	public function get( int $quotation_id ): ?array {
		$table = $this->tables->quotations();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $quotation_id ),
			ARRAY_A
		);
		if ( ! is_array( $row ) ) {
			return null;
		}

		$items_table = $this->tables->quotation_items();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$items = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$items_table} WHERE quotation_id = %d ORDER BY id ASC",
				$quotation_id
			),
			ARRAY_A
		);

		return array(
			'id'           => (int) $row['id'],
			'title'        => (string) $row['title'],
			'status'       => (string) $row['status'],
			'currency'     => (string) $row['currency'],
			'subtotal_twd' => (int) $row['subtotal_twd'],
			'total_twd'    => (int) $row['total_twd'],
			'created_by'   => (int) $row['created_by'],
			'created_at'   => (string) $row['created_at'],
			'updated_at'   => (string) $row['updated_at'],
			'line_items'   => array_map(
				static function ( array $item ): array {
					return array(
						'id'             => (int) $item['id'],
						'service_id'     => isset( $item['service_id'] ) ? (int) $item['service_id'] : null,
						'service_code'   => (string) $item['service_code'],
						'description'    => (string) $item['description'],
						'quantity'       => (int) $item['quantity'],
						'unit_price_twd' => (int) $item['unit_price_twd'],
						'line_total_twd' => (int) $item['line_total_twd'],
					);
				},
				is_array( $items ) ? $items : array()
			),
		);
	}
}
