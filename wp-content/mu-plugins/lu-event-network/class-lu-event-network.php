<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LU_Event_Network {
	private const REST_NAMESPACE = 'lu-events/v1';
	private const THEME_SLUG = 'lu-event';
	private const CAPABILITY = 'lu_build_event_sites';
	private const SETTINGS_OPTION = 'lu_event_settings';
	private const MASTER_PAGE_SLUGS = array( 'support', 'privacy', 'accessibility', 'terms' );

	public static function boot(): void {
		add_action( 'init', array( __CLASS__, 'register_roles' ) );
		add_action( 'init', array( __CLASS__, 'ensure_master_pages' ), 30 );
		add_action( 'acf/init', array( __CLASS__, 'register_acf' ) );
		add_action( 'acf/save_post', array( __CLASS__, 'sync_acf_settings' ), 20 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	public static function register_roles(): void {
		$administrator = get_role( 'administrator' );
		if ( $administrator && ! $administrator->has_cap( self::CAPABILITY ) ) {
			$administrator->add_cap( self::CAPABILITY );
		}

		if ( ! get_role( 'lu_event_builder' ) ) {
			add_role(
				'lu_event_builder',
				__( 'Event Site Builder', 'lu-events' ),
				array(
					'read'                   => true,
					'upload_files'           => true,
					self::CAPABILITY         => true,
				)
			);
		}
	}

	public static function register_acf(): void {
		if ( ! function_exists( 'acf_add_options_page' ) || ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_options_page(
			array(
				'page_title'      => __( 'Event Night Settings', 'lu-events' ),
				'menu_title'      => __( 'Event Night', 'lu-events' ),
				'menu_slug'       => 'lu-event-settings',
				'capability'      => 'manage_options',
				'redirect'        => false,
				'update_button'   => __( 'Update Event Site', 'lu-events' ),
				'updated_message' => __( 'Event site updated.', 'lu-events' ),
			)
		);

		acf_add_local_field_group(
			array(
				'key'      => 'group_lu_event_settings',
				'title'    => __( 'Event Site', 'lu-events' ),
				'fields'   => self::acf_fields(),
				'location' => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => 'lu-event-settings',
						),
					),
				),
				'active'   => true,
				'show_in_rest' => 1,
			)
		);
	}

	private static function acf_fields(): array {
		return array(
			array( 'key' => 'field_lu_event_identity_tab', 'label' => 'Identity', 'name' => '', 'type' => 'tab' ),
			array( 'key' => 'field_lu_event_restaurant_name', 'label' => 'Restaurant / Bar Name', 'name' => 'restaurant_name', 'type' => 'text', 'required' => 1 ),
			array( 'key' => 'field_lu_event_event_name', 'label' => 'Event Name', 'name' => 'event_name', 'type' => 'text', 'required' => 1 ),
			array( 'key' => 'field_lu_event_logo', 'label' => 'Event Logo', 'name' => 'logo', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' ),
			array( 'key' => 'field_lu_event_logo_width', 'label' => 'Header Logo Width', 'name' => 'logo_width', 'type' => 'range', 'instructions' => 'Adjust the maximum width used by horizontal logos in the site header.', 'default_value' => 260, 'min' => 100, 'max' => 420, 'step' => 10, 'append' => 'px' ),
			array( 'key' => 'field_lu_event_content_tab', 'label' => 'Event Content', 'name' => '', 'type' => 'tab' ),
			array( 'key' => 'field_lu_event_eyebrow', 'label' => 'Hero Eyebrow', 'name' => 'eyebrow', 'type' => 'text' ),
			array( 'key' => 'field_lu_event_headline', 'label' => 'Hero Headline', 'name' => 'headline', 'type' => 'textarea', 'rows' => 3 ),
			array( 'key' => 'field_lu_event_intro', 'label' => 'Hero Copy', 'name' => 'intro', 'type' => 'textarea', 'rows' => 4 ),
			array( 'key' => 'field_lu_event_day', 'label' => 'Day', 'name' => 'schedule_day', 'type' => 'select', 'choices' => array_combine( array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ), array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ) ) ),
			array( 'key' => 'field_lu_event_time', 'label' => 'Time', 'name' => 'schedule_time', 'type' => 'text', 'placeholder' => '7 PM' ),
			array( 'key' => 'field_lu_event_team_size', 'label' => 'Team Size', 'name' => 'team_size', 'type' => 'text', 'placeholder' => '2–6' ),
			array( 'key' => 'field_lu_event_assets_tab', 'label' => 'Images', 'name' => '', 'type' => 'tab' ),
			array( 'key' => 'field_lu_event_hero_image', 'label' => 'Hero Crowd Photo', 'name' => 'hero_image', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'large', 'library' => 'all' ),
			array( 'key' => 'field_lu_event_app_screenshot', 'label' => 'Tall App Screenshot', 'name' => 'app_screenshot', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' ),
			array( 'key' => 'field_lu_event_location_image', 'label' => 'Location Photo', 'name' => 'location_image', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' ),
			array( 'key' => 'field_lu_event_brand_tab', 'label' => 'Brand', 'name' => '', 'type' => 'tab' ),
			array( 'key' => 'field_lu_event_primary_color', 'label' => 'Primary Color', 'name' => 'primary_color', 'type' => 'color_picker', 'enable_opacity' => 0 ),
			array( 'key' => 'field_lu_event_accent_color', 'label' => 'Accent Color', 'name' => 'accent_color', 'type' => 'color_picker', 'enable_opacity' => 0 ),
			array( 'key' => 'field_lu_event_highlight_color', 'label' => 'Highlight Color', 'name' => 'highlight_color', 'type' => 'color_picker', 'enable_opacity' => 0 ),
			array( 'key' => 'field_lu_event_default_theme', 'label' => 'Default Theme', 'name' => 'default_theme', 'type' => 'button_group', 'choices' => array( 'dark' => 'Dark', 'light' => 'Light' ), 'default_value' => 'dark' ),
			array( 'key' => 'field_lu_event_theme_toggle', 'label' => 'Allow Light / Dark Toggle', 'name' => 'theme_toggle', 'type' => 'true_false', 'ui' => 1, 'default_value' => 1 ),
			array( 'key' => 'field_lu_event_locations_tab', 'label' => 'Locations', 'name' => '', 'type' => 'tab' ),
			array(
				'key'          => 'field_lu_event_locations',
				'label'        => 'Participating Locations',
				'name'         => 'locations',
				'type'         => 'repeater',
				'layout'       => 'block',
				'button_label' => 'Add Location',
				'sub_fields'   => array(
					array( 'key' => 'field_lu_event_location_name', 'label' => 'Location Name', 'name' => 'name', 'type' => 'text', 'required' => 1 ),
					array( 'key' => 'field_lu_event_location_address', 'label' => 'Street Address', 'name' => 'address', 'type' => 'text' ),
					array( 'key' => 'field_lu_event_location_city', 'label' => 'City', 'name' => 'city', 'type' => 'text' ),
					array( 'key' => 'field_lu_event_location_state', 'label' => 'State', 'name' => 'state', 'type' => 'text', 'wrapper' => array( 'width' => 30 ) ),
					array( 'key' => 'field_lu_event_location_postal', 'label' => 'Postal Code', 'name' => 'postal', 'type' => 'text', 'wrapper' => array( 'width' => 30 ) ),
					array( 'key' => 'field_lu_event_location_url', 'label' => 'Location URL', 'name' => 'url', 'type' => 'url', 'wrapper' => array( 'width' => 40 ) ),
				),
			),
			array( 'key' => 'field_lu_event_launch_tab', 'label' => 'Launch', 'name' => '', 'type' => 'tab' ),
			array( 'key' => 'field_lu_event_domain', 'label' => 'Mapped Domain', 'name' => 'custom_domain', 'type' => 'text', 'instructions' => 'Enter only the hostname, such as cantinachallenge.com. DNS and SSL still need to point to this WordPress network.' ),
			array( 'key' => 'field_lu_event_app_store_url', 'label' => 'App Store URL', 'name' => 'app_store_url', 'type' => 'url' ),
			array( 'key' => 'field_lu_event_play_store_url', 'label' => 'Google Play URL', 'name' => 'play_store_url', 'type' => 'url' ),
		);
	}

	public static function register_rest_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/defaults',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'rest_defaults' ),
				'permission_callback' => array( __CLASS__, 'can_build' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/sites',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'rest_list_sites' ),
					'permission_callback' => array( __CLASS__, 'can_build' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'rest_create_site' ),
					'permission_callback' => array( __CLASS__, 'can_build' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/sites/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'rest_get_site' ),
					'permission_callback' => array( __CLASS__, 'can_build' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( __CLASS__, 'rest_update_site' ),
					'permission_callback' => array( __CLASS__, 'can_build' ),
				),
			)
		);
	}

	public static function can_build(): bool {
		return is_user_logged_in() && ( is_super_admin() || current_user_can( self::CAPABILITY ) );
	}

	public static function rest_defaults(): WP_REST_Response {
		return rest_ensure_response(
			array(
				'settings' => self::defaults(),
				'is_multisite' => is_multisite(),
				'is_local' => 'local' === wp_get_environment_type(),
				'network_domain' => self::network_domain(),
			)
		);
	}

	public static function rest_list_sites(): WP_REST_Response {
		$items = array();
		$main_site_id = get_main_site_id();

		foreach ( get_sites( array( 'number' => 50, 'orderby' => 'registered', 'order' => 'DESC' ) ) as $site ) {
			if ( (int) $site->blog_id === (int) $main_site_id ) {
				continue;
			}
			$items[] = self::site_payload( (int) $site->blog_id );
		}

		return rest_ensure_response( $items );
	}

	public static function rest_get_site( WP_REST_Request $request ) {
		$site_id = absint( $request['id'] );
		if ( ! get_site( $site_id ) ) {
			return new WP_Error( 'lu_event_site_missing', __( 'That event site does not exist.', 'lu-events' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( self::site_payload( $site_id ) );
	}

	public static function rest_create_site( WP_REST_Request $request ) {
		if ( ! is_multisite() ) {
			return new WP_Error( 'lu_event_multisite_required', __( 'WordPress Multisite must be enabled.', 'lu-events' ), array( 'status' => 409 ) );
		}

		$raw = self::request_settings( $request );
		$settings = self::sanitize_settings( $raw );
		$slug = sanitize_title( $request->get_param( 'site_slug' ) ?: $settings['event_name'] );
		$slug = trim( $slug, '/' );
		if ( ! $slug ) {
			return new WP_Error( 'lu_event_slug_required', __( 'Add an event name or site address.', 'lu-events' ), array( 'status' => 400 ) );
		}

		$network = get_network();
		$domain = $network ? $network->domain : wp_parse_url( network_home_url(), PHP_URL_HOST );
		$path = trailingslashit( ( $network ? $network->path : '/' ) . $slug );
		if ( get_blog_id_from_url( $domain, $path ) ) {
			return new WP_Error( 'lu_event_slug_exists', __( 'That site address is already in use.', 'lu-events' ), array( 'status' => 409 ) );
		}

		$site_id = wpmu_create_blog(
			$domain,
			$path,
			$settings['event_name'],
			get_current_user_id(),
			array( 'public' => 1, 'lu_event_site' => 1 ),
			get_current_network_id()
		);
		if ( is_wp_error( $site_id ) ) {
			return $site_id;
		}

		add_user_to_blog( $site_id, get_current_user_id(), 'administrator' );
		$result = self::provision_site( $site_id, $settings, $request->get_file_params() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( self::site_payload( $site_id ), 201 );
	}

	public static function rest_update_site( WP_REST_Request $request ) {
		$site_id = absint( $request['id'] );
		if ( ! get_site( $site_id ) || (int) $site_id === (int) get_main_site_id() ) {
			return new WP_Error( 'lu_event_site_missing', __( 'That event site does not exist.', 'lu-events' ), array( 'status' => 404 ) );
		}

		$raw = self::request_settings( $request );
		$existing = self::get_site_settings( $site_id );
		$settings = self::sanitize_settings( array_merge( $existing, $raw ) );
		$result = self::provision_site( $site_id, $settings, $request->get_file_params(), false );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( self::site_payload( $site_id ) );
	}

	private static function request_settings( WP_REST_Request $request ): array {
		$payload = $request->get_param( 'settings' );
		if ( is_string( $payload ) ) {
			$decoded = json_decode( wp_unslash( $payload ), true );
			$payload = is_array( $decoded ) ? $decoded : array();
		}
		return is_array( $payload ) ? $payload : $request->get_params();
	}

	private static function provision_site( int $site_id, array $settings, array $files = array(), bool $create_pages = true ) {
		switch_to_blog( $site_id );

		if ( wp_get_theme( self::THEME_SLUG )->exists() ) {
			switch_theme( self::THEME_SLUG );
		}
		if ( $create_pages ) {
			self::initialize_site_plugins();
		}
		update_option( 'blogname', $settings['event_name'] );
		update_option( 'blogdescription', sprintf( __( 'A weekly event night at %s.', 'lu-events' ), $settings['restaurant_name'] ) );
		update_option( 'timezone_string', get_option( 'timezone_string' ) ?: 'America/Chicago' );
		update_option( 'permalink_structure', '/%postname%/' );

		$upload_result = self::ingest_uploads( $files );
		if ( is_wp_error( $upload_result ) ) {
			restore_current_blog();
			return $upload_result;
		}
		$settings = array_merge( $settings, $upload_result );
		self::save_settings( $settings );

		if ( $create_pages || ! get_option( 'page_on_front' ) ) {
			self::create_site_pages();
		}
		self::apply_domain_mapping( $site_id, $settings['custom_domain'] );
		flush_rewrite_rules( false );
		restore_current_blog();
		return true;
	}

	/**
	 * Give network-active plugins a chance to create tables scoped to a new site.
	 *
	 * Formidable Forms does not currently initialize all of its tables when a
	 * site is created through wpmu_create_blog(), so its public query hooks can
	 * otherwise log missing-table errors on a freshly provisioned event site.
	 */
	private static function initialize_site_plugins(): void {
		if ( class_exists( 'FrmAppController' ) && is_callable( array( 'FrmAppController', 'install' ) ) ) {
			FrmAppController::install();
		}
	}

	private static function ingest_uploads( array $files ) {
		if ( ! $files ) {
			return array();
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$result = array();
		$allowed = array( 'logo', 'hero_image', 'app_screenshot', 'location_image' );
		foreach ( $allowed as $field ) {
			if ( empty( $files[ $field ]['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $files[ $field ]['error'] ) {
				continue;
			}
			$file_array = array(
				'name'     => sanitize_file_name( $files[ $field ]['name'] ),
				'tmp_name' => $files[ $field ]['tmp_name'],
			);
			$attachment_id = media_handle_sideload( $file_array, 0 );
			if ( is_wp_error( $attachment_id ) ) {
				return $attachment_id;
			}
			$result[ $field ] = (int) $attachment_id;
		}
		return $result;
	}

	private static function create_site_pages(): void {
		$home = get_page_by_path( 'home' );
		$home_id = $home ? (int) $home->ID : wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Home', 'lu-events' ),
				'post_name'    => 'home',
				'post_content' => '',
			)
		);
		if ( $home_id && ! is_wp_error( $home_id ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', (int) $home_id );
		}

		foreach ( self::MASTER_PAGE_SLUGS as $slug ) {
			$page = get_page_by_path( $slug );
			if ( ! $page ) {
				$page_id = wp_insert_post(
					array(
						'post_type'    => 'page',
						'post_status'  => 'publish',
						'post_title'   => self::master_page_title( $slug ),
						'post_name'    => $slug,
						'post_content' => '',
					)
				);
				if ( $page_id && ! is_wp_error( $page_id ) ) {
					update_post_meta( $page_id, '_lu_shared_page_slug', $slug );
				}
			}
		}
	}

	public static function ensure_master_pages(): void {
		if ( ! is_multisite() || (int) get_current_blog_id() !== (int) get_main_site_id() || get_option( 'lu_event_master_pages_seeded' ) ) {
			return;
		}
		foreach ( self::MASTER_PAGE_SLUGS as $slug ) {
			if ( get_page_by_path( $slug ) ) {
				continue;
			}
			wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => self::master_page_title( $slug ),
					'post_name'    => $slug,
					'post_content' => self::master_page_content( $slug ),
				)
			);
		}
		update_option( 'lu_event_master_pages_seeded', gmdate( 'c' ) );
	}

	public static function get_shared_page( string $slug ): ?array {
		$slug = sanitize_title( $slug );
		if ( ! in_array( $slug, self::MASTER_PAGE_SLUGS, true ) ) {
			return null;
		}
		$switched = false;
		if ( is_multisite() && (int) get_current_blog_id() !== (int) get_main_site_id() ) {
			switch_to_blog( get_main_site_id() );
			$switched = true;
		}
		$page = get_page_by_path( $slug );
		$data = $page ? array( 'title' => get_the_title( $page ), 'content' => $page->post_content, 'modified' => $page->post_modified_gmt ) : null;
		if ( $switched ) {
			restore_current_blog();
		}
		return $data;
	}

	private static function master_page_title( string $slug ): string {
		return array(
			'support'       => __( 'Support', 'lu-events' ),
			'privacy'       => __( 'Privacy', 'lu-events' ),
			'accessibility' => __( 'Accessibility', 'lu-events' ),
			'terms'         => __( 'Terms', 'lu-events' ),
		)[ $slug ];
	}

	private static function master_page_content( string $slug ): string {
		$content = array(
			'support' => '<h2>We’re here to help.</h2><p>For help with an event, your team, points, or the app, contact the event host at your participating location. For account or technical support, use the support channel provided inside the app.</p><h2>Before you reach out</h2><p>Include the event name, location, team name, and a short description of what happened. Please do not send passwords or payment information.</p>',
			'privacy' => '<h2>How event sites use information</h2><p>These event sites may process information you choose to submit, basic device and browser data, and interactions needed to operate the experience. Information is used to provide the event, maintain safety and reliability, answer support requests, and understand site performance.</p><h2>Your choices</h2><p>You can limit optional analytics through your browser settings and request help through the support page. Any event app may provide additional privacy details inside the app.</p>',
			'accessibility' => '<h2>Our commitment</h2><p>We want every guest to be able to learn about and participate in event nights. The shared event template supports keyboard navigation, visible focus, reduced motion, responsive text, semantic headings, and color contrast designed for readability.</p><h2>Need another format?</h2><p>If any part of an event site is difficult to use, contact support and include the page, device, browser, and assistance you need.</p>',
			'terms' => '<h2>Using an event site</h2><p>Event details, schedules, prizes, and participating locations may change. Venue rules and app-specific terms also apply. Please participate respectfully and follow instructions from event staff.</p><h2>Content and availability</h2><p>Event sites are provided for informational and participation purposes. Access may be interrupted for maintenance, safety, or circumstances outside the operator’s control.</p>',
		);
		return $content[ $slug ];
	}

	private static function save_settings( array $settings ): void {
		update_option( self::SETTINGS_OPTION, $settings, false );
		if ( ! function_exists( 'update_field' ) ) {
			return;
		}
		foreach ( self::field_map() as $name => $key ) {
			update_field( $key, $settings[ $name ], 'option' );
		}
	}

	/**
	 * Keep front-end settings in sync when an administrator edits the ACF
	 * options page directly on a generated site.
	 *
	 * @param mixed $post_id ACF's saved object identifier.
	 */
	public static function sync_acf_settings( $post_id ): void {
		if ( 'options' !== $post_id || ! function_exists( 'get_field' ) ) {
			return;
		}

		$raw = get_option( self::SETTINGS_OPTION, self::defaults() );
		$raw = is_array( $raw ) ? $raw : self::defaults();
		foreach ( self::field_map() as $name => $key ) {
			$raw[ $name ] = get_field( $key, 'option', false );
		}
		update_option( self::SETTINGS_OPTION, self::sanitize_settings( $raw ), false );
	}

	private static function field_map(): array {
		return array(
			'restaurant_name' => 'field_lu_event_restaurant_name',
			'event_name' => 'field_lu_event_event_name',
			'logo' => 'field_lu_event_logo',
			'logo_width' => 'field_lu_event_logo_width',
			'eyebrow' => 'field_lu_event_eyebrow',
			'headline' => 'field_lu_event_headline',
			'intro' => 'field_lu_event_intro',
			'schedule_day' => 'field_lu_event_day',
			'schedule_time' => 'field_lu_event_time',
			'team_size' => 'field_lu_event_team_size',
			'hero_image' => 'field_lu_event_hero_image',
			'app_screenshot' => 'field_lu_event_app_screenshot',
			'location_image' => 'field_lu_event_location_image',
			'primary_color' => 'field_lu_event_primary_color',
			'accent_color' => 'field_lu_event_accent_color',
			'highlight_color' => 'field_lu_event_highlight_color',
			'default_theme' => 'field_lu_event_default_theme',
			'theme_toggle' => 'field_lu_event_theme_toggle',
			'locations' => 'field_lu_event_locations',
			'custom_domain' => 'field_lu_event_domain',
			'app_store_url' => 'field_lu_event_app_store_url',
			'play_store_url' => 'field_lu_event_play_store_url',
		);
	}

	private static function apply_domain_mapping( int $site_id, string $domain ): void {
		$domain = self::sanitize_domain( $domain );
		update_option( 'lu_event_requested_domain', $domain, false );
		if ( ! $domain || 'local' === wp_get_environment_type() ) {
			return;
		}
		update_blog_details( $site_id, array( 'domain' => $domain, 'path' => '/' ) );
		$scheme = is_ssl() ? 'https' : 'http';
		update_option( 'home', $scheme . '://' . $domain );
		update_option( 'siteurl', $scheme . '://' . $domain );
	}

	private static function site_payload( int $site_id ): array {
		$site = get_site( $site_id );
		$settings = self::get_site_settings( $site_id );
		return array(
			'id' => $site_id,
			'name' => $settings['event_name'],
			'url' => get_home_url( $site_id, '/' ),
			'edit_url' => get_admin_url( $site_id, 'admin.php?page=lu-event-settings' ),
			'domain' => $site ? $site->domain : '',
			'path' => $site ? $site->path : '',
			'domain_status' => $settings['custom_domain'] ? ( 'local' === wp_get_environment_type() ? 'saved_for_launch' : 'mapped' ) : 'not_set',
			'settings' => $settings,
		);
	}

	public static function get_site_settings( int $site_id = 0 ): array {
		$switched = false;
		if ( $site_id && (int) $site_id !== (int) get_current_blog_id() ) {
			switch_to_blog( $site_id );
			$switched = true;
		}
		$saved = get_option( self::SETTINGS_OPTION, array() );
		$settings = array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
		if ( $switched ) {
			restore_current_blog();
		}
		return $settings;
	}

	public static function defaults(): array {
		return array(
			'restaurant_name' => 'barTaco',
			'event_name' => 'Cantina Challenge',
			'logo' => 0,
			'logo_width' => 260,
			'eyebrow' => 'Weekly. Live. Competitive. Fun.',
			'headline' => 'Your crew has a standing reservation.',
			'intro' => 'A new challenge. A live host. A season-long league. One very good reason to make Tuesday yours.',
			'schedule_day' => 'Tuesday',
			'schedule_time' => '7 PM',
			'team_size' => '2–6',
			'hero_image' => 0,
			'app_screenshot' => 0,
			'location_image' => 0,
			'primary_color' => '#f05a24',
			'accent_color' => '#f28a0f',
			'highlight_color' => '#a8c932',
			'default_theme' => 'dark',
			'theme_toggle' => true,
			'locations' => array(
				array( 'name' => 'barTaco Lincoln Park', 'address' => '2201 N. Lincoln Ave', 'city' => 'Chicago', 'state' => 'IL', 'postal' => '60614', 'url' => '' ),
				array( 'name' => 'barTaco River North', 'address' => '435 N. Clark St', 'city' => 'Chicago', 'state' => 'IL', 'postal' => '60654', 'url' => '' ),
				array( 'name' => 'barTaco Wicker Park', 'address' => '1595 N. Milwaukee Ave', 'city' => 'Chicago', 'state' => 'IL', 'postal' => '60622', 'url' => '' ),
			),
			'custom_domain' => '',
			'app_store_url' => '',
			'play_store_url' => '',
		);
	}

	private static function sanitize_settings( array $raw ): array {
		$defaults = self::defaults();
		$locations = array();
		foreach ( (array) ( $raw['locations'] ?? $defaults['locations'] ) as $location ) {
			if ( ! is_array( $location ) || empty( $location['name'] ) ) {
				continue;
			}
			$locations[] = array(
				'name' => sanitize_text_field( $location['name'] ),
				'address' => sanitize_text_field( $location['address'] ?? '' ),
				'city' => sanitize_text_field( $location['city'] ?? '' ),
				'state' => sanitize_text_field( $location['state'] ?? '' ),
				'postal' => sanitize_text_field( $location['postal'] ?? '' ),
				'url' => esc_url_raw( $location['url'] ?? '' ),
			);
		}

		return array(
			'restaurant_name' => sanitize_text_field( $raw['restaurant_name'] ?? $defaults['restaurant_name'] ),
			'event_name' => sanitize_text_field( $raw['event_name'] ?? $defaults['event_name'] ),
			'logo' => absint( $raw['logo'] ?? 0 ),
			'logo_width' => max( 100, min( 420, absint( $raw['logo_width'] ?? $defaults['logo_width'] ) ) ),
			'eyebrow' => sanitize_text_field( $raw['eyebrow'] ?? $defaults['eyebrow'] ),
			'headline' => sanitize_textarea_field( $raw['headline'] ?? $defaults['headline'] ),
			'intro' => sanitize_textarea_field( $raw['intro'] ?? $defaults['intro'] ),
			'schedule_day' => in_array( $raw['schedule_day'] ?? '', array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ), true ) ? $raw['schedule_day'] : $defaults['schedule_day'],
			'schedule_time' => sanitize_text_field( $raw['schedule_time'] ?? $defaults['schedule_time'] ),
			'team_size' => sanitize_text_field( $raw['team_size'] ?? $defaults['team_size'] ),
			'hero_image' => absint( $raw['hero_image'] ?? 0 ),
			'app_screenshot' => absint( $raw['app_screenshot'] ?? 0 ),
			'location_image' => absint( $raw['location_image'] ?? 0 ),
			'primary_color' => sanitize_hex_color( $raw['primary_color'] ?? '' ) ?: $defaults['primary_color'],
			'accent_color' => sanitize_hex_color( $raw['accent_color'] ?? '' ) ?: $defaults['accent_color'],
			'highlight_color' => sanitize_hex_color( $raw['highlight_color'] ?? '' ) ?: $defaults['highlight_color'],
			'default_theme' => 'light' === ( $raw['default_theme'] ?? '' ) ? 'light' : 'dark',
			'theme_toggle' => filter_var( $raw['theme_toggle'] ?? true, FILTER_VALIDATE_BOOLEAN ),
			'locations' => $locations ?: $defaults['locations'],
			'custom_domain' => self::sanitize_domain( $raw['custom_domain'] ?? '' ),
			'app_store_url' => esc_url_raw( $raw['app_store_url'] ?? '' ),
			'play_store_url' => esc_url_raw( $raw['play_store_url'] ?? '' ),
		);
	}

	private static function sanitize_domain( string $domain ): string {
		$domain = strtolower( trim( preg_replace( '#^https?://#i', '', $domain ) ) );
		$domain = trim( explode( '/', $domain )[0] );
		if ( function_exists( 'idn_to_ascii' ) && $domain ) {
			$ascii = idn_to_ascii( $domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46 );
			$domain = $ascii ?: $domain;
		}
		return preg_match( '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $domain ) ? $domain : '';
	}

	private static function network_domain(): string {
		$network = get_network();
		return $network ? $network->domain : (string) wp_parse_url( network_home_url(), PHP_URL_HOST );
	}
}
