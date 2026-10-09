<?php
/**
 * Money calculator tests.
 *
 * @package AnvisionStudio\ProjectOS\Tests
 */

namespace AnvisionStudio\ProjectOS\Tests\Unit;

use AnvisionStudio\ProjectOS\Domain\Money\LineItem;
use AnvisionStudio\ProjectOS\Domain\Money\MoneyCalculator;
use PHPUnit\Framework\TestCase;

final class MoneyCalculatorTest extends TestCase {

	public function test_recalculates_subtotal_tax_and_total(): void {
		$calculator = new MoneyCalculator();
		$totals     = $calculator->calculate(
			'TWD',
			array(
				new LineItem( 'Design', 2, 50000, 500 ),
				new LineItem( 'Hosting', 1, 10000, 0 ),
			)
		);

		$this->assertSame( 110000, $totals->subtotal_minor );
		$this->assertSame( 5000, $totals->tax_minor );
		$this->assertSame( 115000, $totals->total_minor );
	}
}
