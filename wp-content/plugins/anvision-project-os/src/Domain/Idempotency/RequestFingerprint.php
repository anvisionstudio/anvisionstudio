<?php
/**
 * Idempotency request fingerprint.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Domain\Idempotency;

/**
 * Builds stable hashes for Idempotency-Key storage.
 */
final class RequestFingerprint {

	/**
	 * @param array<string, mixed>|null $body Parsed JSON body.
	 */
	public function hash( string $method, string $route, ?array $body ): string {
		$normalized = $this->normalize_body( $body );
		$payload    = strtoupper( $method ) . "\n" . $route . "\n" . $normalized;
		return hash( 'sha256', $payload );
	}

	/**
	 * @param array<string, mixed>|null $body Body.
	 */
	private function normalize_body( ?array $body ): string {
		if ( null === $body || array() === $body ) {
			return '';
		}
		$this->ksort_recursive( $body );
		$encoded = json_encode( $body, JSON_THROW_ON_ERROR );
		return is_string( $encoded ) ? $encoded : '';
	}

	/**
	 * @param array<string, mixed> $data Data to sort.
	 */
	private function ksort_recursive( array &$data ): void {
		ksort( $data );
		foreach ( $data as &$value ) {
			if ( is_array( $value ) ) {
				$this->ksort_recursive( $value );
			}
		}
	}
}
