<?php
/**
 * Anvision Studio theme bootstrap.
 *
 * @package AnvisionStudio
 */

defined( 'ABSPATH' ) || exit;

define( 'ANVISION_THEME_VERSION', '0.1.0' );

/**
 * Theme setup.
 */
function anvision_theme_setup(): void {
	load_theme_textdomain( 'anvisionstudio', get_template_directory() . '/languages' );

	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'build/style-index.css' );
}

add_action( 'after_setup_theme', 'anvision_theme_setup' );

/**
 * Front-end assets compiled by @wordpress/scripts.
 */
function anvision_theme_enqueue_assets(): void {
	$build_dir = get_template_directory() . '/build';
	$build_uri = get_template_directory_uri() . '/build';
	$asset     = $build_dir . '/index.asset.php';

	if ( ! file_exists( $asset ) ) {
		return;
	}

	$meta = include $asset;

	wp_enqueue_style(
		'anvisionstudio',
		$build_uri . '/style-index.css',
		array(),
		$meta['version']
	);

	wp_enqueue_script(
		'anvisionstudio',
		$build_uri . '/index.js',
		$meta['dependencies'],
		$meta['version'],
		true
	);
}

add_action( 'wp_enqueue_scripts', 'anvision_theme_enqueue_assets' );
