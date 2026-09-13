<?php
/**
 * Core plugin singleton.
 *
 * @package AnvisionStudio\Core
 */

namespace AnvisionStudio\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Registers site-specific hooks.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the shared instance.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \RuntimeException Always, because this class is a singleton.
	 */
	public function __wakeup(): void {
		throw new \RuntimeException( 'Cannot unserialize ' . __CLASS__ );
	}

	/**
	 * Wire hooks.
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register_content' ) );
	}

	/**
	 * Theme-adjacent content types live here — extend as the product grows.
	 */
	public function register_content(): void {
		register_post_type(
			'anvision_work',
			array(
				'labels'       => array(
					'name'          => __( 'Works', 'anvisionstudio-core' ),
					'singular_name' => __( 'Work', 'anvisionstudio-core' ),
				),
				'public'       => true,
				'has_archive'  => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-portfolio',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'rewrite'      => array( 'slug' => 'work' ),
			)
		);
	}
}
