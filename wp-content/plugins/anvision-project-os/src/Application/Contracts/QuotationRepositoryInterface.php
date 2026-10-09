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
 * Quotation storage abstraction.
 */
interface QuotationRepositoryInterface {

	/**
	 * @param LineItem[] $items Line items.
	 * @return array<string, mixed>
	 */
	public function create(
		string $title,
		string $review_status,
		string $currency,
		array $items,
		MoneyTotals $totals,
		int $actor_user_id
	): array;

	/**
	 * @return array<string, mixed>|null
	 */
	public function get( int $quotation_id ): ?array;

	/**
	 * @param LineItem[] $items Items.
	 * @return array<string, mixed>
	 */
	public function add_version(
		int $quotation_id,
		string $currency,
		array $items,
		MoneyTotals $totals,
		int $actor_user_id,
		bool $is_snapshot,
		?int $snapshot_of_version_id
	): array;

	/**
	 * @return array<string, mixed>
	 */
	public function snapshot_version( int $quotation_id, int $version_id, int $actor_user_id ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function update_review_status(
		int $quotation_id,
		string $from_status,
		string $to_status,
		int $actor_user_id,
		string $note
	): array;

	/**
	 * @return list<array<string, mixed>>
	 */
	public function audit_log( int $quotation_id ): array;
}
