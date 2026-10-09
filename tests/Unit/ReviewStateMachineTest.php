<?php
/**
 * Review FSM tests.
 *
 * @package AnvisionStudio\ProjectOS\Tests
 */

namespace AnvisionStudio\ProjectOS\Tests\Unit;

use AnvisionStudio\ProjectOS\Domain\Review\ReviewAction;
use AnvisionStudio\ProjectOS\Domain\Review\ReviewStateMachine;
use AnvisionStudio\ProjectOS\Domain\Review\ReviewStatus;
use PHPUnit\Framework\TestCase;

final class ReviewStateMachineTest extends TestCase {

	public function test_submit_from_draft(): void {
		$fsm = new ReviewStateMachine();
		$this->assertSame(
			ReviewStatus::IN_REVIEW,
			$fsm->transition( ReviewStatus::DRAFT, ReviewAction::SUBMIT )
		);
	}

	public function test_reject_invalid_transition(): void {
		$this->expectException( \DomainException::class );
		( new ReviewStateMachine() )->transition( ReviewStatus::DRAFT, ReviewAction::APPROVE );
	}
}
