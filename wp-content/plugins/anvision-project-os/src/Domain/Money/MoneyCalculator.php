<?php
/**
 * Server-side money aggregation.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Domain\Money;

/**
 * Recalculates quotation totals from line items.
 */
final class MoneyCalculator {

	/**
	 * @param LineItem[] $items Line items.
	 */
	public function calculate( string $currency, array $items ): MoneyTotals {
		$subtotal = 0;
		$tax      = 0;

		foreach ( $items as $item ) {
			if ( ! $item instanceof LineItem ) {
				throw new \InvalidArgumentException( 'Invalid line item.' );
			}
			$line_net  = $item->quantity * $item->unit_price_minor;
			$line_tax  = (int) round( $line_net * $item->tax_rate_bps / 10000 );
			$subtotal += $line_net;
			$tax      += $line_tax;
		}

		return new MoneyTotals(
			$currency,
			$subtotal,
			$tax,
			$subtotal + $tax
		);
	}
}
