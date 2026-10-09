<?php
/**
 * REST route registrar.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Http;

/**
 * Registers all Project OS REST controllers.
 */
final class RestRegistrar {

	public function register(): void {
		( new CatalogController() )->register_routes();
		( new QuotationController() )->register_routes();
	}
}
