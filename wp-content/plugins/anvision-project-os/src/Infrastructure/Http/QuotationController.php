<?php
/**
 * Quotation REST controller.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Http;

use AnvisionStudio\ProjectOS\Application\Quotation\AddQuotationVersionService;
use AnvisionStudio\ProjectOS\Application\Quotation\CreateQuotationService;
use AnvisionStudio\ProjectOS\Application\Quotation\CreateSnapshotService;
use AnvisionStudio\ProjectOS\Application\Quotation\ReviewTransitionService;
use AnvisionStudio\ProjectOS\Infrastructure\Persistence\QuotationRepository;
use AnvisionStudio\ProjectOS\Infrastructure\Persistence\TableNames;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Registers quotation routes under avs/v1.
 */
final class QuotationController {

	private CreateQuotationService $create_service;
	private AddQuotationVersionService $version_service;
	private CreateSnapshotService $snapshot_service;
	private ReviewTransitionService $review_service;
	private QuotationRepository $quotations;
	private IdempotencyGuard $idempotency;

	public function __construct() {
		global $wpdb;
		$tables                 = new TableNames( $wpdb );
		$this->quotations       = new QuotationRepository( $wpdb, $tables );
		$this->create_service   = new CreateQuotationService( $this->quotations, new \AnvisionStudio\ProjectOS\Domain\Money\MoneyCalculator() );
		$this->version_service  = new AddQuotationVersionService( $this->quotations, new \AnvisionStudio\ProjectOS\Domain\Money\MoneyCalculator() );
		$this->snapshot_service = new CreateSnapshotService( $this->quotations );
		$this->review_service   = new ReviewTransitionService(
			$this->quotations,
			new \AnvisionStudio\ProjectOS\Domain\Review\ReviewStateMachine()
		);
		$this->idempotency      = new IdempotencyGuard(
			new \AnvisionStudio\ProjectOS\Infrastructure\Persistence\IdempotencyStore( $wpdb, $tables ),
			new \AnvisionStudio\ProjectOS\Domain\Idempotency\RequestFingerprint()
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

		register_rest_route(
			'avs/v1',
			'/quotations/(?P<id>\d+)/versions',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_version' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			'avs/v1',
			'/quotations/(?P<id>\d+)/versions/(?P<version_id>\d+)/snapshot',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_snapshot' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			'avs/v1',
			'/quotations/(?P<id>\d+)/review',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'review_transition' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
	}

	public function can_manage(): bool {
		return current_user_can( 'manage_avs_projects' );
	}

	public function create_quotation( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		return $this->idempotency->wrap(
			$request,
			function ( WP_REST_Request $req ): WP_REST_Response|\WP_Error {
				$params = $req->get_json_params();
				if ( ! is_array( $params ) ) {
					return new \WP_Error( 'avs_invalid_body', 'Expected JSON body.', array( 'status' => 400 ) );
				}
				$title = sanitize_text_field( (string) ( $params['title'] ?? '' ) );
				if ( '' === $title ) {
					return new \WP_Error( 'avs_invalid_title', 'Title is required.', array( 'status' => 400 ) );
				}
				$currency = strtoupper( sanitize_text_field( (string) ( $params['currency'] ?? 'TWD' ) ) );
				$items    = $params['line_items'] ?? array();
				if ( ! is_array( $items ) || array() === $items ) {
					return new \WP_Error( 'avs_invalid_line_items', 'line_items required.', array( 'status' => 400 ) );
				}

				try {
					$result = $this->create_service->execute(
						$title,
						$currency,
						$items,
						get_current_user_id()
					);
				} catch ( \Throwable $e ) {
					return new \WP_Error( 'avs_create_failed', $e->getMessage(), array( 'status' => 400 ) );
				}

				return new WP_REST_Response( $result, 201 );
			}
		);
	}

	public function get_quotation( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$id  = (int) $request['id'];
		$row = $this->quotations->get( $id );
		if ( null === $row ) {
			return new \WP_Error( 'avs_not_found', 'Quotation not found.', array( 'status' => 404 ) );
		}
		return new WP_REST_Response( $row, 200 );
	}

	public function add_version( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		return $this->idempotency->wrap(
			$request,
			function ( WP_REST_Request $req ): WP_REST_Response|\WP_Error {
				$id     = (int) $req['id'];
				$params = $req->get_json_params();
				if ( ! is_array( $params ) ) {
					return new \WP_Error( 'avs_invalid_body', 'Expected JSON body.', array( 'status' => 400 ) );
				}
				$currency = strtoupper( sanitize_text_field( (string) ( $params['currency'] ?? 'TWD' ) ) );
				$items    = $params['line_items'] ?? array();
				if ( ! is_array( $items ) || array() === $items ) {
					return new \WP_Error( 'avs_invalid_line_items', 'line_items required.', array( 'status' => 400 ) );
				}

				try {
					$version = $this->version_service->execute( $id, $currency, $items, get_current_user_id() );
				} catch ( \InvalidArgumentException $e ) {
					return new \WP_Error( 'avs_not_found', $e->getMessage(), array( 'status' => 404 ) );
				} catch ( \Throwable $e ) {
					return new \WP_Error( 'avs_version_failed', $e->getMessage(), array( 'status' => 400 ) );
				}

				return new WP_REST_Response( $version, 201 );
			}
		);
	}

	public function create_snapshot( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		return $this->idempotency->wrap(
			$request,
			function ( WP_REST_Request $req ): WP_REST_Response|\WP_Error {
				$id         = (int) $req['id'];
				$version_id = (int) $req['version_id'];
				try {
					$snapshot = $this->snapshot_service->execute( $id, $version_id, get_current_user_id() );
				} catch ( \InvalidArgumentException $e ) {
					return new \WP_Error( 'avs_snapshot_failed', $e->getMessage(), array( 'status' => 400 ) );
				}

				return new WP_REST_Response( $snapshot, 201 );
			}
		);
	}

	public function review_transition( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		return $this->idempotency->wrap(
			$request,
			function ( WP_REST_Request $req ): WP_REST_Response|\WP_Error {
				$id     = (int) $req['id'];
				$params = $req->get_json_params();
				if ( ! is_array( $params ) ) {
					return new \WP_Error( 'avs_invalid_body', 'Expected JSON body.', array( 'status' => 400 ) );
				}
				$action = sanitize_key( (string) ( $params['action'] ?? '' ) );
				$note   = sanitize_textarea_field( (string) ( $params['note'] ?? '' ) );

				try {
					$result = $this->review_service->execute( $id, $action, get_current_user_id(), $note );
				} catch ( \InvalidArgumentException $e ) {
					return new \WP_Error( 'avs_not_found', $e->getMessage(), array( 'status' => 404 ) );
				} catch ( \DomainException $e ) {
					return new \WP_Error( 'avs_invalid_transition', $e->getMessage(), array( 'status' => 422 ) );
				}

				return new WP_REST_Response( $result, 200 );
			}
		);
	}
}
