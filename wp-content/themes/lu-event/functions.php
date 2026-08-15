<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lu_event_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
}
add_action( 'after_setup_theme', 'lu_event_theme_setup' );

function lu_event_settings(): array {
	if ( class_exists( 'LU_Event_Network' ) ) {
		return LU_Event_Network::get_site_settings();
	}
	return array();
}

function lu_event_media_url( $value, string $fallback = '' ): string {
	if ( is_numeric( $value ) ) {
		$url = wp_get_attachment_image_url( (int) $value, 'full' );
		return $url ?: $fallback;
	}
	if ( is_array( $value ) && ! empty( $value['url'] ) ) {
		return esc_url_raw( $value['url'] );
	}
	if ( is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
		return esc_url_raw( $value );
	}
	return $fallback;
}

function lu_event_theme_asset( string $path ): string {
	return get_template_directory_uri() . '/assets/' . ltrim( $path, '/' );
}

function lu_event_enqueue_assets(): void {
	$settings = lu_event_settings();
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'lu-event', lu_event_theme_asset( 'css/event.css' ), array( 'dashicons' ), '0.1.0' );
	wp_enqueue_script( 'lu-event', lu_event_theme_asset( 'js/event.js' ), array(), '0.1.0', true );
	wp_script_add_data( 'lu-event', 'strategy', 'defer' );

	$primary = sanitize_hex_color( $settings['primary_color'] ?? '' ) ?: '#f05a24';
	$accent = sanitize_hex_color( $settings['accent_color'] ?? '' ) ?: '#f28a0f';
	$highlight = sanitize_hex_color( $settings['highlight_color'] ?? '' ) ?: '#a8c932';
	wp_add_inline_style(
		'lu-event',
		':root{--event-primary:' . $primary . ';--event-accent:' . $accent . ';--event-highlight:' . $highlight . ';}'
	);
	wp_localize_script(
		'lu-event',
		'LUEvent',
		array(
			'defaultTheme' => 'light' === ( $settings['default_theme'] ?? '' ) ? 'light' : 'dark',
			'themeToggle'  => ! empty( $settings['theme_toggle'] ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'lu_event_enqueue_assets' );

function lu_event_body_classes( array $classes ): array {
	$classes[] = 'lu-event-site';
	return $classes;
}
add_filter( 'body_class', 'lu_event_body_classes' );
