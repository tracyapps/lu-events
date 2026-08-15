<?php
$settings = lu_event_settings();
$event_name = $settings['event_name'] ?? get_bloginfo( 'name' );
$restaurant_name = $settings['restaurant_name'] ?? '';
$logo_url = lu_event_media_url( $settings['logo'] ?? 0 );
$default_theme = 'light' === ( $settings['default_theme'] ?? '' ) ? 'light' : 'dark';
?><!doctype html>
<html <?php language_attributes(); ?> data-theme="<?php echo esc_attr( $default_theme ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>try{document.documentElement.dataset.theme=localStorage.getItem('lu-event-theme')||document.documentElement.dataset.theme}catch(e){}</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main-content"><?php esc_html_e( 'Skip to content', 'lu-event' ); ?></a>
<header class="site-header" data-site-header>
	<a class="event-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $event_name ); ?>">
		<?php if ( $logo_url ) : ?>
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $event_name ); ?>">
		<?php else : ?>
			<img class="event-brand__mark" src="<?php echo esc_url( lu_event_theme_asset( 'images/lu-mark.svg' ) ); ?>" alt="">
			<span><strong><?php echo esc_html( $event_name ); ?></strong><small><?php echo esc_html( $restaurant_name ); ?></small></span>
		<?php endif; ?>
	</a>
	<nav class="site-nav" aria-label="<?php esc_attr_e( 'Main navigation', 'lu-event' ); ?>">
		<a href="<?php echo esc_url( home_url( '/#game' ) ); ?>"><?php esc_html_e( 'The Game', 'lu-event' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/#league' ) ); ?>"><?php esc_html_e( 'League', 'lu-event' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/#locations' ) ); ?>"><?php esc_html_e( 'Locations', 'lu-event' ); ?></a>
	</nav>
	<?php if ( ! empty( $settings['theme_toggle'] ) ) : ?>
		<button class="theme-toggle" type="button" data-theme-toggle aria-label="<?php esc_attr_e( 'Toggle light and dark mode', 'lu-event' ); ?>" aria-pressed="false">
			<span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
			<span class="theme-toggle__track"><span></span></span>
			<span class="dashicons dashicons-moon" aria-hidden="true"></span>
		</button>
	<?php endif; ?>
</header>
