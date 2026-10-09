<?php
/**
 * Idempotency fingerprint tests.
 *
 * @package AnvisionStudio\ProjectOS\Tests
 */

namespace AnvisionStudio\ProjectOS\Tests\Unit;

use AnvisionStudio\ProjectOS\Domain\Idempotency\RequestFingerprint;
use PHPUnit\Framework\TestCase;

final class RequestFingerprintTest extends TestCase {

	public function test_same_body_produces_same_hash(): void {
		$fp   = new RequestFingerprint();
		$body = array(
			'b' => 2,
			'a' => 1,
		);
		$h1   = $fp->hash( 'POST', '/avs/v1/quotations', $body );
		$h2   = $fp->hash( 'POST', '/avs/v1/quotations', array( 'a' => 1, 'b' => 2 ) );
		$this->assertSame( $h1, $h2 );
	}

	public function test_different_body_produces_different_hash(): void {
		$fp = new RequestFingerprint();
		$h1 = $fp->hash( 'POST', '/avs/v1/quotations', array( 'title' => 'A' ) );
		$h2 = $fp->hash( 'POST', '/avs/v1/quotations', array( 'title' => 'B' ) );
		$this->assertNotSame( $h1, $h2 );
	}
}
