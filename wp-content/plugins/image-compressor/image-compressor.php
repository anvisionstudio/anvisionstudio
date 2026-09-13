<?php
/**
 * Plugin Name: Image Compressor
 * Description: Client-side WebP image compressor. Images never leave the browser.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.2
 * Author: Anvision Studio
 * Author URI: https://github.com/anvisionstudio
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: image-compressor
 *
 * @package AnvisionStudio\ImageCompressor
 */

defined( 'ABSPATH' ) || exit;

define( 'ANVISION_IC_VERSION', '0.1.0' );
define( 'ANVISION_IC_FILE', __FILE__ );
define( 'ANVISION_IC_DIR', plugin_dir_path( __FILE__ ) );
define( 'ANVISION_IC_URL', plugin_dir_url( __FILE__ ) );

require_once ANVISION_IC_DIR . 'includes/class-plugin.php';

/**
 * Bootstrap the plugin.
 */
function anvision_ic_bootstrap(): void {
	\AnvisionStudio\ImageCompressor\Plugin::instance()->init();
}

add_action( 'plugins_loaded', 'anvision_ic_bootstrap' );
