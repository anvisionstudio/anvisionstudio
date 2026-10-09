<?php
/**
 * Quotation review FSM.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Domain\Review;

/**
 * Validates and applies review state transitions.
 */
final class ReviewStateMachine {

	/**
	 * @var array<string, array<string, string>>
	 */
	private const TRANSITIONS = array(
		ReviewAction::SUBMIT  => array(
			ReviewStatus::DRAFT    => ReviewStatus::IN_REVIEW,
			ReviewStatus::REJECTED => ReviewStatus::IN_REVIEW,
		),
		ReviewAction::APPROVE => array(
			ReviewStatus::IN_REVIEW => ReviewStatus::APPROVED,
		),
		ReviewAction::REJECT  => array(
			ReviewStatus::IN_REVIEW => ReviewStatus::REJECTED,
		),
		ReviewAction::REOPEN  => array(
			ReviewStatus::APPROVED => ReviewStatus::IN_REVIEW,
		),
	);

	/**
	 * @throws \DomainException When transition is invalid.
	 */
	public function transition( string $from, string $action ): string {
		if ( ! isset( self::TRANSITIONS[ $action ] ) ) {
			throw new \DomainException( 'Unknown review action.' );
		}
		$map = self::TRANSITIONS[ $action ];
		if ( ! isset( $map[ $from ] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Domain exception message, not HTML output.
			throw new \DomainException( 'Cannot ' . $action . ' quotation while in ' . $from . '.' );
		}
		return $map[ $from ];
	}
}
