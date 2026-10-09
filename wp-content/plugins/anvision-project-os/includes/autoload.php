<?php
/**
 * PSR-4 style autoload for AnvisionStudio\ProjectOS.
 *
 * @package AnvisionStudio\ProjectOS
 */

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'AnvisionStudio\\ProjectOS\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$relative = substr( $class_name, strlen( $prefix ) );
		$path     = AVS_PROJECT_OS_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);
