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
	$host = getenv( 'MAILPIT_SMTP_HOST' );
	if ( false === $host || '' === $host ) {
		$host = 'mailpit';
	}

	$port = getenv( 'MAILPIT_INTERNAL_SMTP_PORT' );
	if ( false === $port || '' === $port ) {
		$port = '1025';
	}

	$phpmailer->isSMTP();
	// PHPMailer public properties are camelCase by upstream design.
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$phpmailer->Host        = $host;
	$phpmailer->Port        = (int) $port;
	$phpmailer->SMTPAuth    = false;
	$phpmailer->SMTPSecure  = false;
	$phpmailer->SMTPAutoTLS = false;
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
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
 * Replace empty or localhost From addresses so PHPMailer accepts them.
 *
 * @param string $from From address.
 */
function anvision_local_from_email( string $from ): string {
	$at = strrchr( $from, '@' );
	if ( false === $at ) {
		$host = '';
	} else {
		$host = substr( $at, 1 );
	}

	if ( '' === $from || 'localhost' === $host || ! str_contains( $from, '@' ) ) {
		return 'noreply@anvisionstudio.test';
	}

	return $from;
}

add_filter( 'wp_mail_from', 'anvision_local_from_email' );
