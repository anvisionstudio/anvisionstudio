<?php
/**
 * Quotation workflow integration tests.
 *
 * @package AnvisionStudio\ProjectOS\Tests
 */

namespace AnvisionStudio\ProjectOS\Tests\Integration;

use WP_REST_Request;

final class QuotationWorkflowTest extends WPIntegrationTestCase {

	private function sample_payload(): array {
		return array(
			'title'      => 'Website redesign',
			'currency'   => 'TWD',
			'line_items' => array(
				array(
					'description'      => 'UX',
					'quantity'         => 1,
					'unit_price_minor' => 500000,
					'tax_rate_bps'     => 500,
				),
			),
		);
	}

	public function test_server_recalculates_totals_and_review_audit(): void {
		$this->acting_as_admin();

		$create = new WP_REST_Request( 'POST', '/avs/v1/quotations' );
		$create->set_header( 'Content-Type', 'application/json' );
		$create->set_body( wp_json_encode( $this->sample_payload() ) );
		$created = rest_get_server()->dispatch( $create );
		$this->assertSame( 201, $created->get_status() );

		$data    = $created->get_data();
		$id      = (int) $data['id'];
		$version = $data['versions'][0];
		$this->assertSame( 500000, $version['subtotal_minor'] );
		$this->assertSame( 25000, $version['tax_minor'] );
		$this->assertSame( 525000, $version['total_minor'] );

		$submit = new WP_REST_Request( 'POST', "/avs/v1/quotations/{$id}/review" );
		$submit->set_header( 'Content-Type', 'application/json' );
		$submit->set_body( wp_json_encode( array( 'action' => 'submit', 'note' => 'Ready' ) ) );
		$submitted = rest_get_server()->dispatch( $submit );
		$this->assertSame( 200, $submitted->get_status() );
		$this->assertSame( 'in_review', $submitted->get_data()['review_status'] );
		$this->assertNotEmpty( $submitted->get_data()['audit_log'] );

		$snapshot = new WP_REST_Request( 'POST', "/avs/v1/quotations/{$id}/versions/{$version['id']}/snapshot" );
		$snap_res = rest_get_server()->dispatch( $snapshot );
		$this->assertSame( 201, $snap_res->get_status() );
		$this->assertSame( 1, $snap_res->get_data()['is_snapshot'] );
	}
}
