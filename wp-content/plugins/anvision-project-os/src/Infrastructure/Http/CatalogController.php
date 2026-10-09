<?php
/**
 * Service Catalog REST controller (read-only).
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Http;

use AnvisionStudio\ProjectOS\Application\Catalog\ListServicesService;
use AnvisionStudio\ProjectOS\Infrastructure\Persistence\ServiceCatalogRepository;
use AnvisionStudio\ProjectOS\Infrastructure\Persistence\TableNames;

/**
 * Admin-only catalog listing.
 */
final class CatalogController {

	private ListServicesService $list_services;

	public function __construct() {
		global $wpdb;
		$tables              = new TableNames( $wpdb );
		$this->list_services = new ListServicesService( new ServiceCatalogRepository( $wpdb, $tables ) );
	}

	public function register_routes(): void {
		register_rest_route(
			'avs/v1',
			'/services',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_services' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
	}

	public function can_manage(): bool {
		return current_user_can( 'manage_avs_projects' );
	}

	public function list_services() {
		$data = $this->list_services->execute();
		return ApiResponse::success(
			'avs_services_listed',
			'OK',
			$data,
			array( 'count' => count( $data ) )
		);
	}
}
