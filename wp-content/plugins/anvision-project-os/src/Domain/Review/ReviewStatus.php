<?php
/**
 * Quotation review statuses.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Domain\Review;

/**
 * Allowed review_status values.
 */
final class ReviewStatus {

	public const DRAFT     = 'draft';
	public const IN_REVIEW = 'in_review';
	public const APPROVED  = 'approved';
	public const REJECTED  = 'rejected';

	/**
	 * @return list<string>
	 */
	public static function all(): array {
		return array(
			self::DRAFT,
			self::IN_REVIEW,
			self::APPROVED,
			self::REJECTED,
		);
	}
}
