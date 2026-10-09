<?php
/**
 * Idempotency key storage.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Persistence;

use AnvisionStudio\ProjectOS\Application\Contracts\IdempotencyStoreInterface;

/**
 * Persists idempotent HTTP responses in MySQL.
 */
final class IdempotencyStore implements IdempotencyStoreInterface {

	public function __construct(
		private \wpdb $wpdb,
		private TableNames $tables
	) {}

	public function find( string $idempotency_key ): ?array {
		$table = $this->tables->idempotency_keys();
		$now   = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT request_hash, response_code, response_body FROM {$table}
				WHERE idempotency_key = %s AND expires_at > %s",
				$idempotency_key,
				$now
			),
			ARRAY_A
		);
		if ( ! is_array( $row ) ) {
			return null;
		}
		return array(
			'request_hash'  => (string) $row['request_hash'],
			'response_code' => (int) $row['response_code'],
			'response_body' => (string) $row['response_body'],
		);
	}

	public function save(
		string $idempotency_key,
		string $request_hash,
		string $route,
		string $method,
		int $response_code,
		string $response_body,
		int $ttl_seconds = 86400
	): void {
		$now     = current_time( 'mysql', true );
		$expires = gmdate( 'Y-m-d H:i:s', strtotime( $now ) + $ttl_seconds );
		$table   = $this->tables->idempotency_keys();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing_id = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT id FROM {$table} WHERE idempotency_key = %s",
				$idempotency_key
			)
		);

		if ( $existing_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->wpdb->update(
				$table,
				array(
					'request_hash'  => $request_hash,
					'route'         => $route,
					'method'        => strtoupper( $method ),
					'response_code' => $response_code,
					'response_body' => $response_body,
					'expires_at'    => $expires,
				),
				array( 'id' => (int) $existing_id ),
				array( '%s', '%s', '%s', '%d', '%s', '%s' ),
				array( '%d' )
			);
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$this->wpdb->insert(
			$table,
			array(
				'idempotency_key' => $idempotency_key,
				'request_hash'    => $request_hash,
				'route'           => $route,
				'method'          => strtoupper( $method ),
				'response_code'   => $response_code,
				'response_body'   => $response_body,
				'expires_at'      => $expires,
				'created_at'      => $now,
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
	}
}
