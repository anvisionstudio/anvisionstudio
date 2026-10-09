<?php
/**
 * REST idempotency guard.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Http;

use AnvisionStudio\ProjectOS\Application\Contracts\IdempotencyStoreInterface;
use AnvisionStudio\ProjectOS\Domain\Idempotency\RequestFingerprint;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Enforces Idempotency-Key semantics on write routes.
 */
final class IdempotencyGuard {

	public function __construct(
		private IdempotencyStoreInterface $store,
		private RequestFingerprint $fingerprint
	) {}

	/**
	 * @param callable(WP_REST_Request): WP_REST_Response|\WP_Error $handler Core handler.
	 */
	public function wrap( WP_REST_Request $request, callable $handler ): WP_REST_Response|\WP_Error {
		$key = $request->get_header( 'Idempotency-Key' );
		if ( ! is_string( $key ) || '' === trim( $key ) ) {
			return $handler( $request );
		}

		$key  = substr( trim( $key ), 0, 128 );
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			$body = null;
		}

		$route = $request->get_route();
		$hash  = $this->fingerprint->hash( $request->get_method(), $route, $body );

		$existing = $this->store->find( $key );
		if ( null !== $existing ) {
			if ( ! hash_equals( $existing['request_hash'], $hash ) ) {
				return new \WP_Error(
					'avs_idempotency_key_mismatch',
					__( 'Idempotency-Key was reused with a different request payload.', 'anvision-project-os' ),
					array( 'status' => 409 )
				);
			}
			$data = json_decode( $existing['response_body'], true );
			return new WP_REST_Response(
				is_array( $data ) ? $data : array( 'raw' => $existing['response_body'] ),
				$existing['response_code']
			);
		}

		$result = $handler( $request );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = rest_ensure_response( $result );
		$encoded  = wp_json_encode( $response->get_data() );
		if ( ! is_string( $encoded ) ) {
			$encoded = '{}';
		}

		$this->store->save(
			$key,
			$hash,
			$route,
			$request->get_method(),
			(int) $response->get_status(),
			$encoded
		);

		return $response;
	}
}
