<?php
/**
 * Plugin Name: Anvision Studio Core
 * Description: Site-specific functionality for Anvision Studio.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.2
 * Author: Anvision Studio
 * Author URI: https://github.com/anvisionstudio
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: anvisionstudio-core
 *
 * @package AnvisionStudio\Core
 */

defined( 'ABSPATH' ) || exit;

define( 'ANVISION_CORE_VERSION', '0.1.0' );
define( 'ANVISION_CORE_FILE', __FILE__ );
define( 'ANVISION_CORE_DIR', plugin_dir_path( __FILE__ ) );

require_once ANVISION_CORE_DIR . 'includes/class-plugin.php';

/**
 * Bootstrap the plugin.
 */
function anvision_core_bootstrap(): void {
	\AnvisionStudio\Core\Plugin::instance()->init();
}

add_action( 'plugins_loaded', 'anvision_core_bootstrap' );
