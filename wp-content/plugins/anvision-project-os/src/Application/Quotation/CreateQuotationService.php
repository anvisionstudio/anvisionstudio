<?php
/**
 * Create draft quotation use case.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Application\Quotation;

use AnvisionStudio\ProjectOS\Application\Contracts\QuotationRepositoryInterface;
use AnvisionStudio\ProjectOS\Application\Contracts\ServiceCatalogRepositoryInterface;
use AnvisionStudio\ProjectOS\Domain\Money\LineItem;
use AnvisionStudio\ProjectOS\Domain\Money\MoneyCalculator;

/**
 * Creates a draft quotation; prices come from catalog snapshots, totals from server math.
 */
final class CreateQuotationService {

	public function __construct(
		private QuotationRepositoryInterface $quotations,
		private ServiceCatalogRepositoryInterface $catalog,
		private MoneyCalculator $calculator
	) {}

	/**
	 * @param list<array{service_id?:int,service_code?:string,quantity:int,description?:string}> $requested_lines
	 * @return array<string, mixed>
	 */
	public function execute( string $title, array $requested_lines, int $actor_user_id ): array {
		if ( '' === trim( $title ) ) {
			throw new \InvalidArgumentException( 'Title is required.' );
		}
		if ( array() === $requested_lines ) {
			throw new \InvalidArgumentException( 'At least one line item is required.' );
		}

		$items = array();
		foreach ( $requested_lines as $row ) {
			$service = null;
			if ( isset( $row['service_id'] ) ) {
				$service = $this->catalog->find_by_id( (int) $row['service_id'] );
			} elseif ( isset( $row['service_code'] ) && '' !== (string) $row['service_code'] ) {
				$service = $this->catalog->find_by_code( (string) $row['service_code'] );
			}

			if ( null === $service || ! $service->active ) {
				throw new \InvalidArgumentException( 'Unknown or inactive catalog service.' );
			}

			$quantity    = max( 1, (int) ( $row['quantity'] ?? 1 ) );
			$description = isset( $row['description'] ) && '' !== trim( (string) $row['description'] )
				? (string) $row['description']
				: $service->name;

			// Catalog unit price is snapshotted; client-supplied prices/totals are ignored.
			$items[] = new LineItem(
				$description,
				$quantity,
				$service->unit_price_twd,
				$service->id,
				$service->code
			);
		}

		$totals = $this->calculator->calculate( $items );

		return $this->quotations->create_draft( $title, $items, $totals, $actor_user_id );
	}
}
