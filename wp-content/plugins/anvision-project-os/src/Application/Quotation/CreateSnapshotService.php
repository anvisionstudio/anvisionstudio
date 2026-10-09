<?php
/**
 * Snapshot quotation version use case.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Application\Quotation;

use AnvisionStudio\ProjectOS\Application\Contracts\QuotationRepositoryInterface;

/**
 * Creates an immutable snapshot from an existing version.
 */
final class CreateSnapshotService {

	public function __construct( private QuotationRepositoryInterface $quotations ) {}

	/**
	 * @return array<string, mixed>
	 */
	public function execute( int $quotation_id, int $version_id, int $actor_user_id ): array {
		return $this->quotations->snapshot_version( $quotation_id, $version_id, $actor_user_id );
	}
}
