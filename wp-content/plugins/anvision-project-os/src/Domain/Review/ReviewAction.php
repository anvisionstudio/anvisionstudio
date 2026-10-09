<?php
/**
 * Review transition actions.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Domain\Review;

/**
 * Named review transitions.
 */
final class ReviewAction {

	public const SUBMIT  = 'submit';
	public const APPROVE = 'approve';
	public const REJECT  = 'reject';
	public const REOPEN  = 'reopen';
}
