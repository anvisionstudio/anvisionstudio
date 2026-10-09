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

	public function test_recalculates_integer_twd_totals(): void {
		$calculator = new MoneyCalculator();
		$totals     = $calculator->calculate(
			array(
				new LineItem( 'Design', 2, 50000, 1, 'WEB-UX' ),
				new LineItem( 'Hosting', 1, 12000, 3, 'HOST-1Y' ),
			)
		);

		$this->assertSame( 'TWD', $totals->currency );
		$this->assertSame( 112000, $totals->subtotal_twd );
		$this->assertSame( 112000, $totals->total_twd );
	}

	public function test_ignores_client_supplied_concept_by_only_using_line_math(): void {
		// Client cannot inject totals; calculator only sums quantity * unit_price_twd.
		$calculator = new MoneyCalculator();
		$totals     = $calculator->calculate(
			array( new LineItem( 'Build', 1, 120000 ) )
		);
		$this->assertSame( 120000, $totals->total_twd );
	}
}
