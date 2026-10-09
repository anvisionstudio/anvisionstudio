<?php
/**
 * Phase 1 quotation + catalog integration tests.
 *
 * @package AnvisionStudio\ProjectOS\Tests
 */

namespace AnvisionStudio\ProjectOS\Tests\Integration;

use WP_REST_Request;

final class QuotationWorkflowTest extends WPIntegrationTestCase {

	public function test_catalog_is_readable_for_admin(): void {
		$this->acting_as_admin();
		$req = new WP_REST_Request( 'GET', '/avs/v1/services' );
		$res = rest_get_server()->dispatch( $req );
		$this->assertSame( 200, $res->get_status() );
		$body = $res->get_data();
		$this->assertTrue( $body['success'] );
		$this->assertSame( 'avs_services_listed', $body['code'] );
		$this->assertNotEmpty( $body['data'] );
		$this->assertArrayHasKey( 'unit_price_twd', $body['data'][0] );
	}

	public function test_draft_quote_uses_catalog_price_snapshot_and_server_totals(): void {
		$this->acting_as_admin();

		$create = new WP_REST_Request( 'POST', '/avs/v1/quotations' );
		$create->set_header( 'Content-Type', 'application/json' );
		$create->set_body(
			wp_json_encode(
				array(
					'title'      => 'Website redesign',
					// Client-supplied unit_price_twd / total_twd must be ignored.
					'line_items' => array(
						array(
							'service_code'   => 'WEB-UX',
							'quantity'       => 2,
							'unit_price_twd' => 1,
							'total_twd'      => 999999,
						),
					),
				)
			)
		);

		$created = rest_get_server()->dispatch( $create );
		$this->assertSame( 201, $created->get_status() );
		$body = $created->get_data();
		$this->assertTrue( $body['success'] );
		$data = $body['data'];
		$this->assertSame( 'draft', $data['status'] );
		$this->assertSame( 'TWD', $data['currency'] );
		$this->assertSame( 100000, $data['subtotal_twd'] ); // 2 * seeded 50000
		$this->assertSame( 100000, $data['total_twd'] );
		$this->assertSame( 50000, $data['line_items'][0]['unit_price_twd'] );

		$get = new WP_REST_Request( 'GET', '/avs/v1/quotations/' . $data['id'] );
		$got = rest_get_server()->dispatch( $get );
		$this->assertSame( 200, $got->get_status() );
		$this->assertSame( $data['id'], $got->get_data()['data']['id'] );
	}
}
