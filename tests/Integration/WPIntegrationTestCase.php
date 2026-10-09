<?php
/**
 * Base WordPress integration test case.
 *
 * @package AnvisionStudio\ProjectOS\Tests
 */

namespace AnvisionStudio\ProjectOS\Tests\Integration;

use PHPUnit\Framework\TestCase;

if ( class_exists( \WP_UnitTestCase::class ) ) {
	/**
	 * WordPress-backed integration base.
	 */
	abstract class WPIntegrationTestCase extends \WP_UnitTestCase {

		protected function setUp(): void {
			if ( empty( $GLOBALS['avs_wp_tests_loaded'] ) ) {
				$this->markTestSkipped( 'WordPress test library not installed.' );
			}
			parent::setUp();
		}

		protected function acting_as_admin(): int {
			$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
			wp_set_current_user( $user_id );
			return $user_id;
		}
	}
} else {
	/**
	 * Fallback when WordPress test library is absent.
	 */
	abstract class WPIntegrationTestCase extends TestCase {

		protected function setUp(): void {
			$this->markTestSkipped( 'WordPress test library not installed.' );
		}

		protected function acting_as_admin(): int {
			return 0;
		}
	}
}
