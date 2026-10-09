<?php
/**
 * Idempotency persistence port.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Application\Contracts;

/**
 * Stores idempotent request outcomes.
 */
interface IdempotencyStoreInterface {

	/**
	 * @return array{request_hash: string, response_code: int, response_body: string}|null
	 */
	public function find( string $idempotency_key ): ?array;

	public function save(
		string $idempotency_key,
		string $request_hash,
		string $route,
		string $method,
		int $response_code,
		string $response_body,
		int $ttl_seconds = 86400
	): void;
}
