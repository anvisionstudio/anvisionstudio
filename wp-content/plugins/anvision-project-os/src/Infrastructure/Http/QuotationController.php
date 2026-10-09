<?php
/**
 * Quotation REST controller (Phase 1: draft create/get).
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Http;

use AnvisionStudio\ProjectOS\Application\Quotation\CreateQuotationService;
use AnvisionStudio\ProjectOS\Domain\Money\MoneyCalculator;
use AnvisionStudio\ProjectOS\Infrastructure\Persistence\IdempotencyStore;
use AnvisionStudio\ProjectOS\Infrastructure\Persistence\QuotationRepository;
use AnvisionStudio\ProjectOS\Infrastructure\Persistence\ServiceCatalogRepository;
use AnvisionStudio\ProjectOS\Infrastructure\Persistence\TableNames;
use AnvisionStudio\ProjectOS\Domain\Idempotency\RequestFingerprint;
use WP_REST_Request;

/**
 * Registers quotation routes under avs/v1.
 */
final class QuotationController {

	private CreateQuotationService $create_service;
	private QuotationRepository $quotations;
	private IdempotencyGuard $idempotency;

	public function __construct() {
		global $wpdb;
		$tables               = new TableNames( $wpdb );
		$this->quotations     = new QuotationRepository( $wpdb, $tables );
		$catalog              = new ServiceCatalogRepository( $wpdb, $tables );
		$this->create_service = new CreateQuotationService(
			$this->quotations,
			$catalog,
			new MoneyCalculator()
		);
		$this->idempotency    = new IdempotencyGuard(
			new IdempotencyStore( $wpdb, $tables ),
			new RequestFingerprint()
		);
	}

	public function register_routes(): void {
		register_rest_route(
			'avs/v1',
			'/quotations',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_quotation' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			'avs/v1',
			'/quotations/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_quotation' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
	}

	public function can_manage(): bool {
		return current_user_can( 'manage_avs_projects' );
	}

	public function create_quotation( WP_REST_Request $request ) {
		return $this->idempotency->wrap(
			$request,
			function ( WP_REST_Request $req ) {
				$params = $req->get_json_params();
				if ( ! is_array( $params ) ) {
					return ApiResponse::error( 'avs_invalid_body', 'Expected JSON body.', 400 );
				}

				$title = sanitize_text_field( (string) ( $params['title'] ?? '' ) );
				$lines = $params['line_items'] ?? array();
				if ( ! is_array( $lines ) ) {
					return ApiResponse::error( 'avs_invalid_line_items', 'line_items must be an array.', 400 );
				}

				try {
					$result = $this->create_service->execute( $title, $lines, get_current_user_id() );
				} catch ( \InvalidArgumentException $e ) {
					return ApiResponse::error( 'avs_create_failed', $e->getMessage(), 400 );
				} catch ( \Throwable $e ) {
					return ApiResponse::error( 'avs_create_failed', $e->getMessage(), 500 );
				}

				return ApiResponse::success(
					'avs_quotation_created',
					'Draft quotation created.',
					$result,
					array(),
					201
				);
			}
		);
	}

	public function get_quotation( WP_REST_Request $request ) {
		$id  = (int) $request['id'];
		$row = $this->quotations->get( $id );
		if ( null === $row ) {
			return ApiResponse::error( 'avs_not_found', 'Quotation not found.', 404 );
		}
		return ApiResponse::success( 'avs_quotation_retrieved', 'OK', $row );
	}
}
