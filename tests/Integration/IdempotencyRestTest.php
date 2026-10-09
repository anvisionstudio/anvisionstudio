<?php
/**
 * Idempotency REST integration tests.
 *
 * @package AnvisionStudio\ProjectOS\Tests
 */

namespace AnvisionStudio\ProjectOS\Tests\Integration;

use WP_REST_Request;

final class IdempotencyRestTest extends WPIntegrationTestCase {

	private function draft_payload( string $title ): array {
		return array(
			'title'      => $title,
			'line_items' => array(
				array(
					'service_code' => 'WEB-UX',
					'quantity'     => 1,
				),
			),
		);
	}

	public function test_reused_key_with_different_body_returns_409(): void {
		$this->acting_as_admin();
		$key = 'test-key-' . wp_generate_password( 8, false );

		$req1 = new WP_REST_Request( 'POST', '/avs/v1/quotations' );
		$req1->set_header( 'Idempotency-Key', $key );
		$req1->set_header( 'Content-Type', 'application/json' );
		$req1->set_body( wp_json_encode( $this->draft_payload( 'Quote A' ) ) );

		$res1 = rest_get_server()->dispatch( $req1 );
		$this->assertSame( 201, $res1->get_status() );
		$this->assertTrue( $res1->get_data()['success'] );

		$req2 = new WP_REST_Request( 'POST', '/avs/v1/quotations' );
		$req2->set_header( 'Idempotency-Key', $key );
		$req2->set_header( 'Content-Type', 'application/json' );
		$req2->set_body( wp_json_encode( $this->draft_payload( 'Quote B' ) ) );

		$res2 = rest_get_server()->dispatch( $req2 );
		$this->assertSame( 409, $res2->get_status() );
		$this->assertSame( 'avs_idempotency_key_mismatch', $res2->as_error()->get_error_code() );
	}

	public function test_reused_key_with_same_body_replays_response(): void {
		$this->acting_as_admin();
		$key     = 'replay-key-' . wp_generate_password( 8, false );
		$payload = $this->draft_payload( 'Replay' );

		$make_request = static function ( string $idempotency_key ) use ( $payload ): WP_REST_Request {
			$req = new WP_REST_Request( 'POST', '/avs/v1/quotations' );
			$req->set_header( 'Idempotency-Key', $idempotency_key );
			$req->set_header( 'Content-Type', 'application/json' );
			$req->set_body( wp_json_encode( $payload ) );
			return $req;
		};

		$res1 = rest_get_server()->dispatch( $make_request( $key ) );
		$res2 = rest_get_server()->dispatch( $make_request( $key ) );

		$this->assertSame( 201, $res1->get_status() );
		$this->assertSame( 201, $res2->get_status() );
		$this->assertSame( $res1->get_data()['data']['id'], $res2->get_data()['data']['id'] );
	}
}
