<?php
/**
 * Plugin Name: Anvision Local Environment
 * Description: Local SMTP (Mailpit), environment defaults, and development conveniences.
 * Version: 0.1.0
 * Author: Anvision Studio
 *
 * @package AnvisionStudio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Route all mail through Mailpit.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
 */
function anvision_local_configure_mailer( $phpmailer ): void {
	$host = getenv( 'MAILPIT_SMTP_HOST' ) ?: 'mailpit';
	$port = (int) ( getenv( 'MAILPIT_INTERNAL_SMTP_PORT' ) ?: 1025 );

	$phpmailer->isSMTP();
	$phpmailer->Host       = $host;
	$phpmailer->Port       = $port;
	$phpmailer->SMTPAuth   = false;
	$phpmailer->SMTPSecure = false;
	$phpmailer->SMTPAutoTLS = false;
}

add_action( 'phpmailer_init', 'anvision_local_configure_mailer' );

/**
 * Identify local environment in the admin bar.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar.
 */
function anvision_local_admin_bar( $wp_admin_bar ): void {
	$wp_admin_bar->add_node(
		array(
			'id'    => 'anvision-env',
			'title' => 'LOCAL',
			'meta'  => array(
				'class' => 'anvision-env-local',
			),
		)
	);
}

add_action( 'admin_bar_menu', 'anvision_local_admin_bar', 999 );

/**
 * Mark outgoing mail From address as local-safe.
 *
 * @param string $from From address.
 */
function anvision_local_from_email( string $from ): string {
	if ( empty( $from ) ) {
		return 'noreply@anvisionstudio.test';
	}

	return $from;
}

add_filter( 'wp_mail_from', 'anvision_local_from_email' );
