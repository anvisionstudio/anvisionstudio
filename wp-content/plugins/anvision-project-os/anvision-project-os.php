<?php
/**
 * Plugin Name: an_Vision Project OS
 * Description: Project OS backend — quotations, review workflow, and idempotent REST API.
 * Version: 0.2.0
 * Requires at least: 6.6
 * Requires PHP: 8.2
 * Author: Anvision Studio
 * Author URI: https://anvisionstudio.com
 * License: GPL-2.0-or-later
 * Text Domain: anvision-project-os
 *
 * @package AnvisionStudio\ProjectOS
 */

defined( 'ABSPATH' ) || exit;

define( 'AVS_PROJECT_OS_VERSION', '0.2.0' );
define( 'AVS_PROJECT_OS_FILE', __FILE__ );
define( 'AVS_PROJECT_OS_DIR', plugin_dir_path( __FILE__ ) );

require_once AVS_PROJECT_OS_DIR . 'includes/autoload.php';
require_once AVS_PROJECT_OS_DIR . 'includes/class-plugin.php';

/**
 * Bootstrap Project OS.
 */
function avs_project_os_bootstrap(): void {
	\AnvisionStudio\ProjectOS\Infrastructure\WordPress\Plugin::instance()->init();
}

add_action( 'plugins_loaded', 'avs_project_os_bootstrap' );

register_activation_hook(
	__FILE__,
	static function (): void {
		\AnvisionStudio\ProjectOS\Infrastructure\WordPress\Plugin::instance()->activate();
	}
);
