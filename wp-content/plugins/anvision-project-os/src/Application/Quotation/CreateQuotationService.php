<?php
/**
 * Create quotation use case.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Application\Quotation;

use AnvisionStudio\ProjectOS\Domain\Money\LineItem;
use AnvisionStudio\ProjectOS\Domain\Money\MoneyCalculator;
use AnvisionStudio\ProjectOS\Domain\Review\ReviewStatus;
use AnvisionStudio\ProjectOS\Application\Contracts\QuotationRepositoryInterface;

/**
 * Creates a quotation with version 1.
 */
final class CreateQuotationService {

	public function __construct(
		private QuotationRepositoryInterface $quotations,
		private MoneyCalculator $calculator
	) {}

	/**
	 * @param array<int, array<string, mixed>> $line_items Raw line items.
	 * @return array<string, mixed>
	 */
	public function execute( string $title, string $currency, array $line_items, int $actor_user_id ): array {
		$items  = array_map(
			static fn( array $row ): LineItem => LineItem::from_array( $row ),
			$line_items
		);
		$totals = $this->calculator->calculate( $currency, $items );

		return $this->quotations->create(
			$title,
			ReviewStatus::DRAFT,
			$currency,
			$items,
			$totals,
			$actor_user_id
		);
	}
}
