<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lu_generator_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'style', 'script' ) );
}
add_action( 'after_setup_theme', 'lu_generator_setup' );

/**
 * Keep authenticated requests on the exact host and scheme in the browser.
 * This avoids dropping WordPress login cookies when a proxy, www redirect, or
 * mapped network domain differs from the canonical URL stored in WordPress.
 */
function lu_generator_same_origin_url( string $url ): string {
	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) ) {
		return $url;
	}
	$relative = $parts['path'] ?? '/';
	if ( ! empty( $parts['query'] ) ) {
		$relative .= '?' . $parts['query'];
	}
	return $relative;
}

function lu_generator_refresh_rest_nonce(): void {
	if ( ! is_user_logged_in() || ( ! is_super_admin() && ! current_user_can( 'lu_build_event_sites' ) ) ) {
		wp_send_json_error( array( 'message' => __( 'Your event-builder session is not authorized.', 'lu-events-generator' ) ), 403 );
	}
	wp_send_json_success( array( 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
}
add_action( 'wp_ajax_lu_generator_nonce', 'lu_generator_refresh_rest_nonce' );

function lu_generator_enqueue_assets(): void {
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'lu-generator', get_template_directory_uri() . '/assets/css/generator.css', array( 'dashicons' ), '0.2.1' );
	wp_enqueue_script( 'lu-generator', get_template_directory_uri() . '/assets/js/generator.js', array(), '0.2.1', true );
	wp_script_add_data( 'lu-generator', 'strategy', 'defer' );
	wp_localize_script(
		'lu-generator',
		'LUBuilder',
		array(
			'sitesUrl' => lu_generator_same_origin_url( rest_url( 'lu-events/v1/sites' ) ),
			'nonceUrl' => lu_generator_same_origin_url( admin_url( 'admin-ajax.php?action=lu_generator_nonce' ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'networkReady' => class_exists( 'LU_Event_Network' ),
			'homeUrl' => home_url( '/' ),
			'logoutUrl' => wp_logout_url( home_url( '/' ) ),
			'eventAssets' => content_url( 'themes/lu-event/assets/images/' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'lu_generator_enqueue_assets' );
