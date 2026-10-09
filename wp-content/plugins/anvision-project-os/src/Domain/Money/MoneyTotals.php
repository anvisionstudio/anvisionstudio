<?php
/**
 * Calculated monetary totals.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Domain\Money;

/**
 * Immutable totals in minor units.
 */
final class MoneyTotals {

	public function __construct(
		public readonly string $currency,
		public readonly int $subtotal_minor,
		public readonly int $tax_minor,
		public readonly int $total_minor
	) {}

	/**
	 * @return array<string, int|string>
	 */
	public function to_array(): array {
		return array(
			'currency'       => $this->currency,
			'subtotal_minor' => $this->subtotal_minor,
			'tax_minor'      => $this->tax_minor,
			'total_minor'    => $this->total_minor,
		);
	}
}
