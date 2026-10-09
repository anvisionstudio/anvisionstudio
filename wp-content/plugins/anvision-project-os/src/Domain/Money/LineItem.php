<?php
/**
 * Quotation line item value object.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Domain\Money;

/**
 * Immutable line item input.
 */
final class LineItem {

	/**
	 * @param string $description Line label.
	 * @param int    $quantity    Whole units (minimum 1).
	 * @param int    $unit_price_minor Minor-unit price per unit.
	 * @param int    $tax_rate_bps Tax rate in basis points (900 = 9%).
	 */
	public function __construct(
		public readonly string $description,
		public readonly int $quantity,
		public readonly int $unit_price_minor,
		public readonly int $tax_rate_bps = 0
	) {
		if ( $this->quantity < 1 ) {
			throw new \InvalidArgumentException( 'Quantity must be at least 1.' );
		}
		if ( $this->unit_price_minor < 0 ) {
			throw new \InvalidArgumentException( 'Unit price cannot be negative.' );
		}
		if ( $this->tax_rate_bps < 0 || $this->tax_rate_bps > 10000 ) {
			throw new \InvalidArgumentException( 'Tax rate bps out of range.' );
		}
	}

	/**
	 * @param array<string, mixed> $row Raw API/database row fragment.
	 */
	public static function from_array( array $row ): self {
		return new self(
			(string) ( $row['description'] ?? '' ),
			max( 1, (int) ( $row['quantity'] ?? 1 ) ),
			(int) ( $row['unit_price_minor'] ?? 0 ),
			(int) ( $row['tax_rate_bps'] ?? 0 )
		);
	}

	/**
	 * @return array<string, int|string>
	 */
	public function to_array(): array {
		return array(
			'description'      => $this->description,
			'quantity'         => $this->quantity,
			'unit_price_minor' => $this->unit_price_minor,
			'tax_rate_bps'     => $this->tax_rate_bps,
		);
	}
}
