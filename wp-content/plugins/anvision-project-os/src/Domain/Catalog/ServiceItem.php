<?php
/**
 * Service catalog item.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Domain\Catalog;

/**
 * Read-only catalog entry used to snapshot prices onto quotes.
 */
final class ServiceItem {

	public function __construct(
		public readonly int $id,
		public readonly string $code,
		public readonly string $name,
		public readonly string $description,
		public readonly int $unit_price_twd,
		public readonly bool $active
	) {
		if ( $this->unit_price_twd < 0 ) {
			throw new \InvalidArgumentException( 'Unit price cannot be negative.' );
		}
	}

	/**
	 * @return array<string, int|string|bool>
	 */
	public function to_array(): array {
		return array(
			'id'             => $this->id,
			'code'           => $this->code,
			'name'           => $this->name,
			'description'    => $this->description,
			'unit_price_twd' => $this->unit_price_twd,
			'active'         => $this->active,
		);
	}
}
