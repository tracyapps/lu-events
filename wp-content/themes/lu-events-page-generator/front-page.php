<?php
$can_build = is_user_logged_in() && ( is_super_admin() || current_user_can( 'lu_build_event_sites' ) );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'lu-generator' ); ?>>
<?php wp_body_open(); ?>
<?php if ( ! is_user_logged_in() ) : ?>
	<main class="login-shell">
		<section class="login-panel">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/lu-horizontal-orange.png' ); ?>" alt="<?php esc_attr_e( 'Loyalty Untapped', 'lu-events-generator' ); ?>">
			<p class="kicker"><?php esc_html_e( 'Event site studio', 'lu-events-generator' ); ?></p>
			<h1><?php esc_html_e( 'Turn next Tuesday into something real.', 'lu-events-generator' ); ?></h1>
			<p><?php esc_html_e( 'Sign in to build, brand, preview, and launch a client event site from one screen.', 'lu-events-generator' ); ?></p>
			<?php wp_login_form( array( 'redirect' => home_url( '/' ), 'remember' => true ) ); ?>
		</section>
		<div class="login-art" role="img" aria-label="<?php esc_attr_e( 'Loyalty Untapped brand mark', 'lu-events-generator' ); ?>"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/lu-mark.svg' ); ?>" alt=""></div>
	</main>
<?php elseif ( ! $can_build ) : ?>
	<main class="login-shell"><section class="login-panel"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/lu-horizontal-orange.png' ); ?>" alt="Loyalty Untapped"><h1><?php esc_html_e( 'You’re signed in, but this studio isn’t on your keyring yet.', 'lu-events-generator' ); ?></h1><p><?php esc_html_e( 'Ask a network administrator to assign the Event Site Builder role.', 'lu-events-generator' ); ?></p><a class="generator-button generator-button--primary" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Sign out', 'lu-events-generator' ); ?></a></section></main>
<?php else : ?>
	<header class="studio-header">
		<div class="studio-brand"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/lu-horizontal-orange.png' ); ?>" alt="Loyalty Untapped"><span>Event site studio</span></div>
		<div class="studio-header__meta"><span class="studio-status"><i></i><?php esc_html_e( 'Network ready', 'lu-events-generator' ); ?></span><span><?php echo esc_html( wp_get_current_user()->display_name ); ?></span><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Sign out', 'lu-events-generator' ); ?></a></div>
	</header>

	<main class="studio">
		<aside class="builder-panel">
			<div class="builder-panel__intro"><p class="kicker"><?php esc_html_e( 'New event night', 'lu-events-generator' ); ?></p><h1><?php esc_html_e( 'Build the pitch before the meeting.', 'lu-events-generator' ); ?></h1><p><?php esc_html_e( 'Everything here updates the preview instantly. Launch creates a real child site with these settings.', 'lu-events-generator' ); ?></p></div>
			<form id="event-builder" enctype="multipart/form-data">
				<fieldset>
					<legend><span>01</span><?php esc_html_e( 'Event identity', 'lu-events-generator' ); ?></legend>
					<label><?php esc_html_e( 'Restaurant / bar name', 'lu-events-generator' ); ?><input name="restaurant_name" value="barTaco" required></label>
					<label><?php esc_html_e( 'Event name', 'lu-events-generator' ); ?><input name="event_name" value="Cantina Challenge" required></label>
					<label><?php esc_html_e( 'Child-site address', 'lu-events-generator' ); ?><span class="slug-input"><span><?php echo esc_html( wp_parse_url( network_home_url(), PHP_URL_HOST ) ); ?>/</span><input name="site_slug" value="cantina-challenge" required></span></label>
					<label class="file-field"><span><?php esc_html_e( 'Event logo (optional)', 'lu-events-generator' ); ?></span><input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml"><small><?php esc_html_e( 'SVG, PNG, JPG, or WebP', 'lu-events-generator' ); ?></small></label>
				</fieldset>

				<fieldset>
					<legend><span>02</span><?php esc_html_e( 'Story and schedule', 'lu-events-generator' ); ?></legend>
					<label><?php esc_html_e( 'Eyebrow', 'lu-events-generator' ); ?><input name="eyebrow" value="Weekly. Live. Competitive. Fun."></label>
					<label><?php esc_html_e( 'Hero headline', 'lu-events-generator' ); ?><textarea name="headline" rows="3">Your crew has a standing reservation.</textarea></label>
					<label><?php esc_html_e( 'Hero copy', 'lu-events-generator' ); ?><textarea name="intro" rows="4">A new challenge. A live host. A season-long league. One very good reason to make Tuesday yours.</textarea></label>
					<div class="field-row field-row--3">
						<label><?php esc_html_e( 'Day', 'lu-events-generator' ); ?><select name="schedule_day"><?php foreach ( array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ) as $day ) : ?><option <?php selected( 'Tuesday', $day ); ?>><?php echo esc_html( $day ); ?></option><?php endforeach; ?></select></label>
						<label><?php esc_html_e( 'Time', 'lu-events-generator' ); ?><input name="schedule_time" value="7 PM"></label>
						<label><?php esc_html_e( 'Team size', 'lu-events-generator' ); ?><input name="team_size" value="2–6"></label>
					</div>
				</fieldset>

				<fieldset>
					<legend><span>03</span><?php esc_html_e( 'Brand and mood', 'lu-events-generator' ); ?></legend>
					<div class="color-fields">
						<label><?php esc_html_e( 'Primary', 'lu-events-generator' ); ?><input type="color" name="primary_color" value="#f05a24"><output>#f05a24</output></label>
						<label><?php esc_html_e( 'Accent', 'lu-events-generator' ); ?><input type="color" name="accent_color" value="#f28a0f"><output>#f28a0f</output></label>
						<label><?php esc_html_e( 'Highlight', 'lu-events-generator' ); ?><input type="color" name="highlight_color" value="#a8c932"><output>#a8c932</output></label>
					</div>
					<div class="field-row">
						<label><?php esc_html_e( 'Default theme', 'lu-events-generator' ); ?><select name="default_theme"><option value="dark"><?php esc_html_e( 'Dark', 'lu-events-generator' ); ?></option><option value="light"><?php esc_html_e( 'Light', 'lu-events-generator' ); ?></option></select></label>
						<label class="switch-field"><input type="checkbox" name="theme_toggle" checked><span></span><?php esc_html_e( 'Let guests switch themes', 'lu-events-generator' ); ?></label>
					</div>
					<div class="upload-grid">
						<label class="file-field"><span><?php esc_html_e( 'Hero crowd photo', 'lu-events-generator' ); ?></span><input type="file" name="hero_image" accept="image/png,image/jpeg,image/webp"><small><?php esc_html_e( 'Wide landscape', 'lu-events-generator' ); ?></small></label>
						<label class="file-field"><span><?php esc_html_e( 'Tall app screenshot', 'lu-events-generator' ); ?></span><input type="file" name="app_screenshot" accept="image/png,image/jpeg,image/webp"><small><?php esc_html_e( 'Phone-screen content only', 'lu-events-generator' ); ?></small></label>
						<label class="file-field"><span><?php esc_html_e( 'Location photo', 'lu-events-generator' ); ?></span><input type="file" name="location_image" accept="image/png,image/jpeg,image/webp"><small><?php esc_html_e( 'Storefront or venue', 'lu-events-generator' ); ?></small></label>
					</div>
				</fieldset>

				<fieldset>
					<legend><span>04</span><?php esc_html_e( 'Locations', 'lu-events-generator' ); ?></legend>
					<div data-location-editors>
						<div class="location-editor">
							<label><?php esc_html_e( 'Location name', 'lu-events-generator' ); ?><input data-location-field="name" value="barTaco Lincoln Park"></label>
							<label><?php esc_html_e( 'Address', 'lu-events-generator' ); ?><input data-location-field="address" value="2201 N. Lincoln Ave"></label>
							<div class="field-row field-row--3"><label><?php esc_html_e( 'City', 'lu-events-generator' ); ?><input data-location-field="city" value="Chicago"></label><label><?php esc_html_e( 'State', 'lu-events-generator' ); ?><input data-location-field="state" value="IL"></label><label><?php esc_html_e( 'ZIP', 'lu-events-generator' ); ?><input data-location-field="postal" value="60614"></label></div>
							<label><?php esc_html_e( 'Location URL (optional)', 'lu-events-generator' ); ?><input type="url" data-location-field="url"></label>
							<button class="text-button" type="button" data-remove-location hidden><?php esc_html_e( 'Remove location', 'lu-events-generator' ); ?></button>
						</div>
					</div>
					<button class="generator-button generator-button--ghost" type="button" data-add-location><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Add another location', 'lu-events-generator' ); ?></button>
				</fieldset>

				<fieldset>
					<legend><span>05</span><?php esc_html_e( 'Launch details', 'lu-events-generator' ); ?></legend>
					<label><?php esc_html_e( 'Mapped domain (optional)', 'lu-events-generator' ); ?><input name="custom_domain" placeholder="cantinachallenge.com"><small><?php esc_html_e( 'On Local, this is saved without changing the working local URL. In production, it maps natively after DNS and SSL are ready.', 'lu-events-generator' ); ?></small></label>
				</fieldset>

				<div class="launch-bar">
					<div><strong><?php esc_html_e( 'Ready when you are.', 'lu-events-generator' ); ?></strong><small><?php esc_html_e( 'Creates the site, pages, theme settings, and media.', 'lu-events-generator' ); ?></small></div>
					<button class="generator-button generator-button--primary" type="submit"><span><?php esc_html_e( 'Create event site', 'lu-events-generator' ); ?></span><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></button>
				</div>
				<div class="launch-result" data-launch-result aria-live="polite"></div>
			</form>

			<section class="recent-sites" aria-labelledby="recent-sites-title">
				<div><p class="kicker"><?php esc_html_e( 'Network', 'lu-events-generator' ); ?></p><h2 id="recent-sites-title"><?php esc_html_e( 'Recent event sites', 'lu-events-generator' ); ?></h2></div>
				<div data-recent-sites><p class="loading-line"><?php esc_html_e( 'Checking the network…', 'lu-events-generator' ); ?></p></div>
			</section>
		</aside>

		<section class="preview-panel" aria-label="<?php esc_attr_e( 'Live event site preview', 'lu-events-generator' ); ?>">
			<div class="preview-toolbar"><div><span class="studio-status"><i></i><?php esc_html_e( 'Live preview', 'lu-events-generator' ); ?></span><strong data-preview-url>lu-events.local/cantina-challenge/</strong></div><div class="preview-devices" role="group" aria-label="<?php esc_attr_e( 'Preview size', 'lu-events-generator' ); ?>"><button type="button" class="is-active" data-preview-size="desktop" aria-label="Desktop preview"><span class="dashicons dashicons-desktop"></span></button><button type="button" data-preview-size="mobile" aria-label="Mobile preview"><span class="dashicons dashicons-smartphone"></span></button></div></div>
			<div class="preview-stage" data-preview-stage>
				<div class="preview-canvas" data-preview-canvas data-preview-theme="dark">
					<header class="event-preview-header"><div class="event-preview-brand" data-preview-logo><img src="<?php echo esc_url( content_url( 'themes/lu-event/assets/images/lu-mark.svg' ) ); ?>" alt=""><span><strong data-preview-event>Cantina Challenge</strong><small data-preview-restaurant>barTaco</small></span></div><nav><span>The Game</span><span>League</span><span>Locations</span></nav><button type="button" data-preview-theme-toggle aria-label="Toggle preview theme"><span class="dashicons dashicons-lightbulb"></span></button></header>
					<div class="event-preview-banner"><span class="dashicons dashicons-controls-volumeon"></span><strong data-preview-schedule>Live every Tuesday · 7 PM · Teams of 2–6</strong></div>
					<section class="event-preview-hero">
						<div class="event-preview-copy"><p data-preview-eyebrow>Weekly. Live. Competitive. Fun.</p><h2 data-preview-headline>Your crew has a standing reservation.</h2><div data-preview-intro>A new challenge. A live host. A season-long league. One very good reason to make Tuesday yours.</div><span class="preview-cta">Choose a location <i class="dashicons dashicons-arrow-right-alt2"></i></span><span class="preview-cta preview-cta--outline">Meet the challenge</span></div>
						<div class="event-preview-phone"><div><img data-preview-app src="<?php echo esc_url( content_url( 'themes/lu-event/assets/images/app-screen-tall.png' ) ); ?>" alt=""></div><img src="<?php echo esc_url( content_url( 'themes/lu-event/assets/images/phone-frame.png' ) ); ?>" alt=""></div>
						<div class="event-preview-crowd" data-preview-hero style="background-image:url('<?php echo esc_url( content_url( 'themes/lu-event/assets/images/hero-crowd.jpg' ) ); ?>')"></div>
					</section>
					<section class="event-preview-steps"><h3>How the league works</h3><div><span><i class="dashicons dashicons-location-alt"></i><b>Check in</b></span><span><i class="dashicons dashicons-awards"></i><b>Play live</b></span><span><i class="dashicons dashicons-star-filled"></i><b>Earn points</b></span><span><i class="dashicons dashicons-calendar-alt"></i><b>Return next week</b></span></div></section>
				</div>
			</div>
		</section>
	</main>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
