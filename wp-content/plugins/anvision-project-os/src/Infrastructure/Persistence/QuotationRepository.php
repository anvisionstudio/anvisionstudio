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
 * Persists quotations, versions, and audit entries.
 */
final class QuotationRepository implements QuotationRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
		private TableNames $tables
	) {}

	/**
	 * @param LineItem[] $items Items.
	 */
	public function create(
		string $title,
		string $review_status,
		string $currency,
		array $items,
		MoneyTotals $totals,
		int $actor_user_id
	): array {
		$now = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$this->wpdb->insert(
			$this->tables->quotations(),
			array(
				'title'              => $title,
				'review_status'      => $review_status,
				'current_version_id' => null,
				'created_at'         => $now,
				'updated_at'         => $now,
			),
			array( '%s', '%s', '%d', '%s', '%s' )
		);
		$quotation_id = (int) $this->wpdb->insert_id;

		$version = $this->insert_version(
			$quotation_id,
			1,
			$currency,
			$items,
			$totals,
			$actor_user_id,
			false,
			null
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->update(
			$this->tables->quotations(),
			array(
				'current_version_id' => $version['id'],
				'updated_at'         => $now,
			),
			array( 'id' => $quotation_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return $this->get( $quotation_id ) ?? array();
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

		$versions                  = $this->list_versions( $quotation_id );
		$row['versions']           = $versions;
		$row['audit_log']          = $this->audit_log( $quotation_id );
		$row['id']                 = (int) $row['id'];
		$row['current_version_id'] = isset( $row['current_version_id'] ) ? (int) $row['current_version_id'] : null;

		return $row;
	}

	/**
	 * @param LineItem[] $items Items.
	 */
	public function add_version(
		int $quotation_id,
		string $currency,
		array $items,
		MoneyTotals $totals,
		int $actor_user_id,
		bool $is_snapshot,
		?int $snapshot_of_version_id
	): array {
		$next    = $this->next_version_number( $quotation_id );
		$version = $this->insert_version(
			$quotation_id,
			$next,
			$currency,
			$items,
			$totals,
			$actor_user_id,
			$is_snapshot,
			$snapshot_of_version_id
		);

		if ( ! $is_snapshot ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->wpdb->update(
				$this->tables->quotations(),
				array(
					'current_version_id' => $version['id'],
					'updated_at'         => current_time( 'mysql', true ),
				),
				array( 'id' => $quotation_id ),
				array( '%d', '%s' ),
				array( '%d' )
			);
		}

		return $version;
	}

	public function snapshot_version( int $quotation_id, int $version_id, int $actor_user_id ): array {
		$source = $this->get_version( $quotation_id, $version_id );
		if ( null === $source ) {
			throw new \InvalidArgumentException( 'Version not found.' );
		}
		if ( 1 === (int) $source['is_snapshot'] ) {
			throw new \InvalidArgumentException( 'Cannot snapshot a snapshot.' );
		}

		$items = $source['line_items'] ?? array();
		if ( ! is_array( $items ) ) {
			$items = array();
		}
		$items  = array_map(
			static function ( $item ): LineItem {
				return $item instanceof LineItem ? $item : LineItem::from_array( (array) $item );
			},
			$items
		);
		$totals = new MoneyTotals(
			(string) $source['currency'],
			(int) $source['subtotal_minor'],
			(int) $source['tax_minor'],
			(int) $source['total_minor']
		);

		return $this->add_version(
			$quotation_id,
			(string) $source['currency'],
			$items,
			$totals,
			$actor_user_id,
			true,
			$version_id
		);
	}

	public function update_review_status(
		int $quotation_id,
		string $from_status,
		string $to_status,
		int $actor_user_id,
		string $note
	): array {
		$now = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->update(
			$this->tables->quotations(),
			array(
				'review_status' => $to_status,
				'updated_at'    => $now,
			),
			array(
				'id'            => $quotation_id,
				'review_status' => $from_status,
			),
			array( '%s', '%s' ),
			array( '%d', '%s' )
		);

		if ( 0 === (int) $this->wpdb->rows_affected ) {
			throw new \DomainException( 'Review status changed concurrently; retry.' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$this->wpdb->insert(
			$this->tables->quotation_audit_log(),
			array(
				'quotation_id'  => $quotation_id,
				'from_status'   => $from_status,
				'to_status'     => $to_status,
				'actor_user_id' => $actor_user_id,
				'note'          => $note,
				'created_at'    => $now,
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);

		return $this->get( $quotation_id ) ?? array();
	}

	public function audit_log( int $quotation_id ): array {
		$table = $this->tables->quotation_audit_log();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$table} WHERE quotation_id = %d ORDER BY id ASC",
				$quotation_id
			),
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return array();
		}
		return array_map(
			static function ( array $row ): array {
				$row['id']            = (int) $row['id'];
				$row['quotation_id']  = (int) $row['quotation_id'];
				$row['actor_user_id'] = (int) $row['actor_user_id'];
				return $row;
			},
			$rows
		);
	}

	/**
	 * @param LineItem[] $items Items.
	 * @return array<string, mixed>
	 */
	private function insert_version(
		int $quotation_id,
		int $version_number,
		string $currency,
		array $items,
		MoneyTotals $totals,
		int $actor_user_id,
		bool $is_snapshot,
		?int $snapshot_of_version_id
	): array {
		$encoded_items = wp_json_encode(
			array_map(
				static fn( LineItem $item ): array => $item->to_array(),
				$items
			)
		);
		if ( ! is_string( $encoded_items ) ) {
			$encoded_items = '[]';
		}

		$now = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$this->wpdb->insert(
			$this->tables->quotation_versions(),
			array(
				'quotation_id'           => $quotation_id,
				'version_number'         => $version_number,
				'is_snapshot'            => $is_snapshot ? 1 : 0,
				'snapshot_of_version_id' => $snapshot_of_version_id,
				'currency'               => strtoupper( $currency ),
				'subtotal_minor'         => $totals->subtotal_minor,
				'tax_minor'              => $totals->tax_minor,
				'total_minor'            => $totals->total_minor,
				'line_items_json'        => $encoded_items,
				'created_by'             => $actor_user_id,
				'created_at'             => $now,
			),
			array( '%d', '%d', '%d', '%d', '%s', '%d', '%d', '%d', '%s', '%d', '%s' )
		);

		$id  = (int) $this->wpdb->insert_id;
		$row = $this->get_version_by_id( $id );
		return is_array( $row ) ? $row : array( 'id' => $id );
	}

	private function next_version_number( int $quotation_id ): int {
		$table = $this->tables->quotation_versions();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$max = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT MAX(version_number) FROM {$table} WHERE quotation_id = %d",
				$quotation_id
			)
		);
		return max( 1, (int) $max + 1 );
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function list_versions( int $quotation_id ): array {
		$table = $this->tables->quotation_versions();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$table} WHERE quotation_id = %d ORDER BY version_number ASC",
				$quotation_id
			),
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return array();
		}
		return array_map( array( $this, 'normalize_version_row' ), $rows );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function get_version( int $quotation_id, int $version_id ): ?array {
		$table = $this->tables->quotation_versions();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$table} WHERE quotation_id = %d AND id = %d",
				$quotation_id,
				$version_id
			),
			ARRAY_A
		);
		return is_array( $row ) ? $this->normalize_version_row( $row ) : null;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function get_version_by_id( int $version_id ): ?array {
		$table = $this->tables->quotation_versions();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $version_id ),
			ARRAY_A
		);
		return is_array( $row ) ? $this->normalize_version_row( $row ) : null;
	}

	/**
	 * @param array<string, mixed> $row Raw row.
	 * @return array<string, mixed>
	 */
	private function normalize_version_row( array $row ): array {
		$row['id']                     = (int) $row['id'];
		$row['quotation_id']           = (int) $row['quotation_id'];
		$row['version_number']         = (int) $row['version_number'];
		$row['is_snapshot']            = (int) $row['is_snapshot'];
		$row['snapshot_of_version_id'] = isset( $row['snapshot_of_version_id'] ) ? (int) $row['snapshot_of_version_id'] : null;
		$row['subtotal_minor']         = (int) $row['subtotal_minor'];
		$row['tax_minor']              = (int) $row['tax_minor'];
		$row['total_minor']            = (int) $row['total_minor'];
		$row['created_by']             = (int) $row['created_by'];
		$row['line_items']             = $this->decode_items( (string) $row['line_items_json'] );
		unset( $row['line_items_json'] );
		return $row;
	}

	/**
	 * @return LineItem[]
	 */
	private function decode_items( string $json ): array {
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			return array();
		}
		return array_map(
			static fn( array $row ): LineItem => LineItem::from_array( $row ),
			$data
		);
	}
}
