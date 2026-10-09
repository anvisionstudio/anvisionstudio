<?php
/**
 * Calculated monetary totals (integer TWD).
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Domain\Money;

/**
 * Immutable totals.
 */
final class MoneyTotals {

	public function __construct(
		public readonly string $currency,
		public readonly int $subtotal_twd,
		public readonly int $total_twd
	) {}

	/**
	 * @return array<string, int|string>
	 */
	public function to_array(): array {
		return array(
			'currency'     => $this->currency,
			'subtotal_twd' => $this->subtotal_twd,
			'total_twd'    => $this->total_twd,
		);
	}
}
