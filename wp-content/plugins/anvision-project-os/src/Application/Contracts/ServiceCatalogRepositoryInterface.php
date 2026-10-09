<?php
/**
 * Service catalog persistence port.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Application\Contracts;

use AnvisionStudio\ProjectOS\Domain\Catalog\ServiceItem;

/**
 * Read-only catalog access for Phase 1.
 */
interface ServiceCatalogRepositoryInterface {

	/**
	 * @return list<ServiceItem>
	 */
	public function list_active(): array;

	public function find_by_id( int $service_id ): ?ServiceItem;

	public function find_by_code( string $code ): ?ServiceItem;
}
