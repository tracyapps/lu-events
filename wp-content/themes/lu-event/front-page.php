<?php
get_header();

$settings = lu_event_settings();
$event_name = $settings['event_name'] ?? 'Cantina Challenge';
$restaurant_name = $settings['restaurant_name'] ?? 'barTaco';
$day = $settings['schedule_day'] ?? 'Tuesday';
$time = $settings['schedule_time'] ?? '7 PM';
$team_size = $settings['team_size'] ?? '2–6';
$locations = ! empty( $settings['locations'] ) && is_array( $settings['locations'] ) ? $settings['locations'] : array();
$hero_image = lu_event_media_url( $settings['hero_image'] ?? 0, lu_event_theme_asset( 'images/hero-crowd.jpg' ) );
$app_screen = lu_event_media_url( $settings['app_screenshot'] ?? 0, lu_event_theme_asset( 'images/app-screen-tall.png' ) );
$location_image = lu_event_media_url( $settings['location_image'] ?? 0, lu_event_theme_asset( 'images/location-cantina.jpg' ) );
?>
<main id="main-content">
	<section class="live-banner" aria-label="<?php esc_attr_e( 'Event schedule', 'lu-event' ); ?>">
		<span class="dashicons dashicons-controls-volumeon" aria-hidden="true"></span>
		<strong><?php echo esc_html( sprintf( 'Live every %1$s · %2$s · Teams of %3$s', $day, $time, $team_size ) ); ?></strong>
	</section>

	<section class="hero-scroll-stage" data-phone-stage>
		<div class="hero hero--sticky">
			<div class="hero__copy">
				<p class="eyebrow"><?php echo esc_html( $settings['eyebrow'] ?? '' ); ?></p>
				<h1><?php echo nl2br( esc_html( $settings['headline'] ?? '' ) ); ?></h1>
				<p class="hero__intro"><?php echo nl2br( esc_html( $settings['intro'] ?? '' ) ); ?></p>
				<div class="hero__actions">
					<a class="button button--primary" href="#locations"><?php esc_html_e( 'Choose a location', 'lu-event' ); ?><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></a>
					<a class="button button--secondary" href="#game"><?php esc_html_e( 'Meet the challenge', 'lu-event' ); ?></a>
				</div>
				<p class="hero__promise"><span class="dashicons dashicons-carrot" aria-hidden="true"></span><?php echo esc_html( sprintf( 'Good food. Cold drinks. Zero boring %ss.', $day ) ); ?></p>
			</div>

			<div class="phone-scene" data-parallax-scene>
				<img class="phone-scene__motif" src="<?php echo esc_url( lu_event_theme_asset( 'images/lu-mark.svg' ) ); ?>" alt="">
				<div class="phone" data-phone>
					<div class="phone__screen" data-phone-viewport>
						<img src="<?php echo esc_url( $app_screen ); ?>" alt="<?php echo esc_attr( sprintf( '%s app showing league standings and weekly challenges', $event_name ) ); ?>" data-phone-screen>
					</div>
					<img class="phone__frame" src="<?php echo esc_url( lu_event_theme_asset( 'images/phone-frame.png' ) ); ?>" alt="">
				</div>
			</div>

			<div class="hero__crowd" style="--hero-photo:url('<?php echo esc_url( $hero_image ); ?>')" role="img" aria-label="<?php esc_attr_e( 'Friends enjoying a lively restaurant event night', 'lu-event' ); ?>"></div>
		</div>
	</section>

	<section class="how-it-works" id="game">
		<div class="section-shell">
			<p class="eyebrow"><?php esc_html_e( 'The weekly ritual', 'lu-event' ); ?></p>
			<h2><?php esc_html_e( 'How the league works', 'lu-event' ); ?></h2>
			<div class="steps">
				<article><span class="dashicons dashicons-location-alt" aria-hidden="true"></span><h3><?php esc_html_e( 'Check in', 'lu-event' ); ?></h3><p><?php esc_html_e( 'Arrive early, grab a seat, and check in with your host.', 'lu-event' ); ?></p></article>
				<article><span class="dashicons dashicons-awards" aria-hidden="true"></span><h3><?php esc_html_e( 'Play live', 'lu-event' ); ?></h3><p><?php esc_html_e( 'Answer questions. Take on challenges. Compete with other teams.', 'lu-event' ); ?></p></article>
				<article><span class="dashicons dashicons-star-filled" aria-hidden="true"></span><h3><?php esc_html_e( 'Earn points', 'lu-event' ); ?></h3><p><?php esc_html_e( 'Climb the leaderboard all season long and earn bragging rights.', 'lu-event' ); ?></p></article>
				<article><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span><h3><?php esc_html_e( 'Return next week', 'lu-event' ); ?></h3><p><?php echo esc_html( sprintf( 'Same night. New game. Your standing reservation is waiting.', $day ) ); ?></p></article>
			</div>
		</div>
	</section>

	<section class="locations" id="locations">
		<div class="section-shell locations__grid">
			<div class="locations__content">
				<p class="eyebrow"><?php esc_html_e( 'Find your crew’s home base', 'lu-event' ); ?></p>
				<h2><?php echo esc_html( sprintf( 'Find your %s', strtolower( $restaurant_name ) ) ); ?></h2>
				<p><?php echo esc_html( sprintf( 'Join the league at a %s near you.', $restaurant_name ) ); ?></p>
				<label class="location-search">
					<span class="screen-reader-text"><?php esc_html_e( 'Search locations', 'lu-event' ); ?></span>
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<input type="search" placeholder="<?php esc_attr_e( 'Enter city, state, or ZIP code', 'lu-event' ); ?>" data-location-search>
				</label>
				<div class="location-list" data-location-list>
					<?php foreach ( $locations as $index => $location ) :
						$search = implode( ' ', array_filter( array( $location['name'] ?? '', $location['address'] ?? '', $location['city'] ?? '', $location['state'] ?? '', $location['postal'] ?? '' ) ) );
						$url = ! empty( $location['url'] ) ? $location['url'] : '#';
						?>
						<a class="location-row" href="<?php echo esc_url( $url ); ?>" data-location="<?php echo esc_attr( strtolower( $search ) ); ?>">
							<span><strong><?php echo esc_html( $location['name'] ?? '' ); ?></strong><small><?php echo esc_html( implode( ', ', array_filter( array( $location['address'] ?? '', $location['city'] ?? '', $location['state'] ?? '', $location['postal'] ?? '' ) ) ) ); ?></small></span>
							<em><?php esc_html_e( 'Open details', 'lu-event' ); ?></em><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
						</a>
					<?php endforeach; ?>
					<p class="location-list__empty" data-location-empty hidden><?php esc_html_e( 'No matching locations yet. Try a nearby city or ZIP code.', 'lu-event' ); ?></p>
				</div>
			</div>
			<img class="locations__photo" src="<?php echo esc_url( $location_image ); ?>" alt="<?php echo esc_attr( sprintf( '%s location at night', $restaurant_name ) ); ?>">
		</div>
	</section>

	<section class="app-cta" id="league">
		<div class="section-shell app-cta__inner">
			<span class="dashicons dashicons-smartphone" aria-hidden="true"></span>
			<div><h2><?php esc_html_e( 'The game lives in your pocket.', 'lu-event' ); ?></h2><p><?php esc_html_e( 'Follow your standing. Get reminders. See the questions. Earn rewards. All in one place.', 'lu-event' ); ?></p></div>
			<div class="app-cta__links">
				<?php if ( ! empty( $settings['app_store_url'] ) ) : ?><a class="button button--secondary" href="<?php echo esc_url( $settings['app_store_url'] ); ?>"><?php esc_html_e( 'Download for iPhone', 'lu-event' ); ?></a><?php endif; ?>
				<?php if ( ! empty( $settings['play_store_url'] ) ) : ?><a class="button button--secondary" href="<?php echo esc_url( $settings['play_store_url'] ); ?>"><?php esc_html_e( 'Download for Android', 'lu-event' ); ?></a><?php endif; ?>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
