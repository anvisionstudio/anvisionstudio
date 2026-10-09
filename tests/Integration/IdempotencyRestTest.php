<?php
/**
 * Idempotency REST integration tests.
 *
 * @package AnvisionStudio\ProjectOS\Tests
 */

namespace AnvisionStudio\ProjectOS\Tests\Integration;

use WP_REST_Request;

final class IdempotencyRestTest extends WPIntegrationTestCase {

	public function test_reused_key_with_different_body_returns_409(): void {
		$this->acting_as_admin();
		$key = 'test-key-' . wp_generate_password( 8, false );

		$payload_a = array(
			'title'      => 'Quote A',
			'currency'   => 'TWD',
			'line_items' => array(
				array(
					'description'      => 'Item',
					'quantity'         => 1,
					'unit_price_minor' => 1000,
					'tax_rate_bps'     => 0,
				),
			),
		);

		$req1 = new WP_REST_Request( 'POST', '/avs/v1/quotations' );
		$req1->set_header( 'Idempotency-Key', $key );
		$req1->set_body_params( $payload_a );
		$req1->set_header( 'Content-Type', 'application/json' );
		$req1->set_body( wp_json_encode( $payload_a ) );

		$res1 = rest_get_server()->dispatch( $req1 );
		$this->assertSame( 201, $res1->get_status() );

		$payload_b         = $payload_a;
		$payload_b['title'] = 'Quote B';
		$req2              = new WP_REST_Request( 'POST', '/avs/v1/quotations' );
		$req2->set_header( 'Idempotency-Key', $key );
		$req2->set_header( 'Content-Type', 'application/json' );
		$req2->set_body( wp_json_encode( $payload_b ) );

		$res2 = rest_get_server()->dispatch( $req2 );
		$this->assertSame( 409, $res2->get_status() );
		$this->assertSame( 'avs_idempotency_key_mismatch', $res2->as_error()->get_error_code() );
	}

	public function test_reused_key_with_same_body_replays_response(): void {
		$this->acting_as_admin();
		$key = 'replay-key-' . wp_generate_password( 8, false );
		$payload = array(
			'title'      => 'Replay',
			'currency'   => 'TWD',
			'line_items' => array(
				array(
					'description'      => 'Item',
					'quantity'         => 1,
					'unit_price_minor' => 2000,
					'tax_rate_bps'     => 0,
				),
			),
		);

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
		$this->assertSame( $res1->get_data()['id'], $res2->get_data()['id'] );
	}
}
