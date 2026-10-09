<?php
/**
 * Quotation persistence port.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Application\Contracts;

use AnvisionStudio\ProjectOS\Domain\Money\LineItem;
use AnvisionStudio\ProjectOS\Domain\Money\MoneyTotals;

/**
 * Draft quotation storage.
 */
interface QuotationRepositoryInterface {

	/**
	 * @param LineItem[] $items Line items with snapshotted catalog prices.
	 * @return array<string, mixed>
	 */
	public function create_draft(
		string $title,
		array $items,
		MoneyTotals $totals,
		int $actor_user_id
	): array;

	/**
	 * @return array<string, mixed>|null
	 */
	public function get( int $quotation_id ): ?array;
}
