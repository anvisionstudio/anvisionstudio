<?php
/**
 * Migration contract.
 *
 * @package AnvisionStudio\ProjectOS
 */

namespace AnvisionStudio\ProjectOS\Infrastructure\Persistence;

/**
 * Versioned schema migration.
 */
interface MigrationInterface {

	public function version(): string;

	public function up(): void;
}
