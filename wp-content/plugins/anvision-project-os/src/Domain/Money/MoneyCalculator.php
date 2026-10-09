<?php
/**
 * Server-side money aggregation (integer TWD).
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Domain\Money;

/**
 * Recalculates quotation totals from line items. Never trusts client totals.
 */
final class MoneyCalculator {

	/**
	 * @param LineItem[] $items Line items.
	 */
	public function calculate( array $items ): MoneyTotals {
		$subtotal = 0;

		foreach ( $items as $item ) {
			if ( ! $item instanceof LineItem ) {
				throw new \InvalidArgumentException( 'Invalid line item.' );
			}
			$subtotal += $item->line_total_twd();
		}

		return new MoneyTotals( 'TWD', $subtotal, $subtotal );
	}
}
