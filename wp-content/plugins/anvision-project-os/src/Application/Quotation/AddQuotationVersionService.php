<?php
/**
 * Add quotation version use case.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Application\Quotation;

use AnvisionStudio\ProjectOS\Domain\Money\LineItem;
use AnvisionStudio\ProjectOS\Domain\Money\MoneyCalculator;
use AnvisionStudio\ProjectOS\Application\Contracts\QuotationRepositoryInterface;

/**
 * Adds a new editable version with recalculated totals.
 */
final class AddQuotationVersionService {

	public function __construct(
		private QuotationRepositoryInterface $quotations,
		private MoneyCalculator $calculator
	) {}

	/**
	 * @param array<int, array<string, mixed>> $line_items Raw items.
	 * @return array<string, mixed>
	 */
	public function execute( int $quotation_id, string $currency, array $line_items, int $actor_user_id ): array {
		$quotation = $this->quotations->get( $quotation_id );
		if ( null === $quotation ) {
			throw new \InvalidArgumentException( 'Quotation not found.' );
		}

		$items  = array_map(
			static fn( array $row ): LineItem => LineItem::from_array( $row ),
			$line_items
		);
		$totals = $this->calculator->calculate( $currency, $items );

		return $this->quotations->add_version(
			$quotation_id,
			$currency,
			$items,
			$totals,
			$actor_user_id,
			false,
			null
		);
	}
}
