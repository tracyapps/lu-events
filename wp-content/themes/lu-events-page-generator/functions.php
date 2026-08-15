<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lu_generator_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'style', 'script' ) );
}
add_action( 'after_setup_theme', 'lu_generator_setup' );

function lu_generator_enqueue_assets(): void {
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'lu-generator', get_template_directory_uri() . '/assets/css/generator.css', array( 'dashicons' ), '0.1.0' );
	wp_enqueue_script( 'lu-generator', get_template_directory_uri() . '/assets/js/generator.js', array(), '0.1.0', true );
	wp_script_add_data( 'lu-generator', 'strategy', 'defer' );
	wp_localize_script(
		'lu-generator',
		'LUBuilder',
		array(
			'restUrl' => esc_url_raw( rest_url( 'lu-events/v1' ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'homeUrl' => home_url( '/' ),
			'logoutUrl' => wp_logout_url( home_url( '/' ) ),
			'eventAssets' => content_url( 'themes/lu-event/assets/images/' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'lu_generator_enqueue_assets' );
