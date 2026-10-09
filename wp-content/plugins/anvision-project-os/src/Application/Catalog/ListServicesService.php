<?php
/**
 * List active catalog services.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Application\Catalog;

use AnvisionStudio\ProjectOS\Application\Contracts\ServiceCatalogRepositoryInterface;

/**
 * Read-only Service Catalog use case.
 */
final class ListServicesService {

	public function __construct( private ServiceCatalogRepositoryInterface $catalog ) {}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function execute(): array {
		$out = array();
		foreach ( $this->catalog->list_active() as $service ) {
			$out[] = $service->to_array();
		}
		return $out;
	}
}
