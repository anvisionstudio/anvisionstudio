<?php
/**
 * Image Compressor plugin loader.
 *
 * @package AnvisionStudio\ImageCompressor
 */

namespace AnvisionStudio\ImageCompressor;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the admin tool and frontend shortcode.
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
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_shortcode( 'image_compressor', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Media submenu: 圖片壓縮.
	 */
	public function register_admin_page(): void {
		add_media_page(
			__( '圖片壓縮', 'image-compressor' ),
			__( '圖片壓縮', 'image-compressor' ),
			'upload_files',
			'image-compressor',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Admin screen.
	 */
	public function render_admin_page(): void {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'image-compressor' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( '圖片壓縮', 'image-compressor' ) . '</h1>';
		echo '<p>' . esc_html__( '全程在你的瀏覽器處理，圖片不會上傳。', 'image-compressor' ) . '</p>';
		$this->render_iframe();
		echo '</div>';
	}

	/**
	 * Frontend embed: [image_compressor]
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function render_shortcode( $atts ): string {
		unset( $atts );
		ob_start();
		$this->render_iframe();
		return (string) ob_get_clean();
	}

	/**
	 * Same-origin iframe of the static client-side app.
	 */
	private function render_iframe(): void {
		$src = plugins_url( 'app/index.html', ANVISION_IC_FILE );
		printf(
			'<iframe class="anvision-ic-frame" title="%1$s" src="%2$s" loading="lazy"></iframe>',
			esc_attr__( '圖片壓縮工具', 'image-compressor' ),
			esc_url( $src )
		);
		echo '<style>.anvision-ic-frame{width:100%;min-height:840px;border:1px solid #d0cbc3;border-radius:8px;background:#f7f4ef;}</style>';
	}
}
