<?php
/**
 * PHPUnit bootstrap.
 *
 * @package AnvisionStudio\ProjectOS\Tests
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

$GLOBALS['avs_wp_tests_loaded'] = false;

$wp_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! is_string( $wp_tests_dir ) || '' === $wp_tests_dir ) {
	$wp_tests_dir = dirname( __DIR__ ) . '/tmp/wordpress-tests-lib';
}

$wp_tests_config = dirname( __DIR__ ) . '/wp-tests-config.php';
if ( ! is_readable( $wp_tests_dir . '/includes/functions.php' ) || ! is_readable( $wp_tests_config ) ) {
	fwrite( STDOUT, "WordPress test library missing — integration tests will be skipped.\n" );
	return;
}

define( 'WP_TESTS_CONFIG_FILE_PATH', $wp_tests_config );
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

require_once $wp_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__ ) . '/wp-content/plugins/anvision-project-os/anvision-project-os.php';
	}
);

tests_add_filter(
	'setup_theme',
	static function (): void {
		\AnvisionStudio\ProjectOS\Infrastructure\WordPress\Plugin::instance()->activate();
	}
);

require_once $wp_tests_dir . '/includes/bootstrap.php';
$GLOBALS['avs_wp_tests_loaded'] = true;
