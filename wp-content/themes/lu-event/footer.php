<?php $settings = lu_event_settings(); ?>
<footer class="site-footer">
	<div class="site-footer__brand">
		<strong><?php echo esc_html( $settings['event_name'] ?? get_bloginfo( 'name' ) ); ?></strong>
		<span><?php echo esc_html( $settings['restaurant_name'] ?? '' ); ?></span>
	</div>
	<nav aria-label="<?php esc_attr_e( 'Footer navigation', 'lu-event' ); ?>">
		<a href="<?php echo esc_url( home_url( '/#game' ) ); ?>"><?php esc_html_e( 'The Game', 'lu-event' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/#league' ) ); ?>"><?php esc_html_e( 'League', 'lu-event' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/#locations' ) ); ?>"><?php esc_html_e( 'Locations', 'lu-event' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/support/' ) ); ?>"><?php esc_html_e( 'Support', 'lu-event' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>"><?php esc_html_e( 'Privacy', 'lu-event' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/accessibility/' ) ); ?>"><?php esc_html_e( 'Accessibility', 'lu-event' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>"><?php esc_html_e( 'Terms', 'lu-event' ); ?></a>
	</nav>
	<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $settings['restaurant_name'] ?? get_bloginfo( 'name' ) ); ?></p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
