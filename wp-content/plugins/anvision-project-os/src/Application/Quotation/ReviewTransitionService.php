<?php
/**
 * Review transition use case.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Application\Quotation;

use AnvisionStudio\ProjectOS\Domain\Review\ReviewStateMachine;
use AnvisionStudio\ProjectOS\Application\Contracts\QuotationRepositoryInterface;

/**
 * Applies review FSM transitions with audit logging.
 */
final class ReviewTransitionService {

	public function __construct(
		private QuotationRepositoryInterface $quotations,
		private ReviewStateMachine $state_machine
	) {}

	/**
	 * @return array<string, mixed>
	 */
	public function execute( int $quotation_id, string $action, int $actor_user_id, string $note = '' ): array {
		$quotation = $this->quotations->get( $quotation_id );
		if ( null === $quotation ) {
			throw new \InvalidArgumentException( 'Quotation not found.' );
		}

		$from = (string) $quotation['review_status'];
		$to   = $this->state_machine->transition( $from, $action );

		return $this->quotations->update_review_status(
			$quotation_id,
			$from,
			$to,
			$actor_user_id,
			$note
		);
	}
}
