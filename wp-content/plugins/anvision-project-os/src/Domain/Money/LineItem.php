<?php
/**
 * Quotation line item value object.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Domain\Money;

/**
 * Immutable line item. Unit price is integer TWD snapshotted from catalog.
 */
final class LineItem {

	/**
	 * @param string   $description     Line label.
	 * @param int      $quantity        Whole units (minimum 1).
	 * @param int      $unit_price_twd  Integer TWD unit price.
	 * @param int|null $service_id      Optional catalog service id.
	 * @param string   $service_code    Optional catalog service code snapshot.
	 */
	public function __construct(
		public readonly string $description,
		public readonly int $quantity,
		public readonly int $unit_price_twd,
		public readonly ?int $service_id = null,
		public readonly string $service_code = ''
	) {
		if ( $this->quantity < 1 ) {
			throw new \InvalidArgumentException( 'Quantity must be at least 1.' );
		}
		if ( $this->unit_price_twd < 0 ) {
			throw new \InvalidArgumentException( 'Unit price cannot be negative.' );
		}
	}

	public function line_total_twd(): int {
		return $this->quantity * $this->unit_price_twd;
	}

	/**
	 * @return array<string, int|string|null>
	 */
	public function to_array(): array {
		return array(
			'description'    => $this->description,
			'quantity'       => $this->quantity,
			'unit_price_twd' => $this->unit_price_twd,
			'line_total_twd' => $this->line_total_twd(),
			'service_id'     => $this->service_id,
			'service_code'   => $this->service_code,
		);
	}
}
