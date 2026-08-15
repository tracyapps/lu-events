<?php
/**
 * Pro Gated Content Controller
 *
 * Handles Pro-side runtime logic for gated content:
 * file download protection, and future view/PDF gating.
 *
 * All methods are static — no constructor.
 *
 * @package Formidable Pro
 *
 * @since 6.33
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProGatedContentController {

	/**
	 * Enable the `frm_file` gated content item type.
	 *
	 * Hooked to `frm_gated_content_item_types`.
	 *
	 * @param array<string, array> $types Registered item types.
	 *
	 * @return array<string, array>
	 */
	public static function enable_file_item_type( $types ) {
		if ( isset( $types['frm_file'] ) ) {
			$types['frm_file']['disabled'] = false;
			$types['frm_file']['label']    = __( 'Formidable file', 'formidable-pro' );
		}
		return $types;
	}

	/**
	 * Create a FrmProGatedItemFile for the 'frm_file' type.
	 *
	 * Hooked to `frm_gated_item_make` (priority 10).
	 *
	 * @param FrmGatedItem|null                   $instance Current item instance (null = not yet handled).
	 * @param array{type: string, id: int|string} $item     Item data array with 'type' and 'id' keys.
	 *
	 * @return FrmGatedItem|null
	 */
	public static function make_gated_item( $instance, $item ) {
		if ( 'frm_file' !== $item['type'] ) {
			return $instance;
		}

		return new FrmProGatedItemFile( $item );
	}

	/**
	 * Revoke existing tokens for an action+entry pair when the entry is updated.
	 *
	 * Hooked to `frm_trigger_gated_content_action` at priority 5 — before the Lite
	 * trigger (priority 10) generates the new token — so the old token is gone before
	 * the fresh one is written, preventing the old link from remaining valid.
	 *
	 * Only fires when the event is 'update'; all other events are ignored.
	 *
	 * @param WP_Post  $action Form action post object.
	 * @param stdClass $entry  Submitted form entry object.
	 * @param object   $form   Form object.
	 * @param string   $event  Trigger event slug.
	 *
	 * @return void
	 */
	public static function revoke_on_update( $action, $entry, $form, $event ) {
		if ( 'update' !== $event ) {
			return;
		}

		$settings = FrmAppHelper::maybe_json_decode( $action->post_content );

		if ( ! empty( $settings['keep_token_on_update'] ) ) {
			return;
		}

		FrmProGatedTokenHelper::delete_by_action_and_entry( $action->ID, $entry->id );
	}

	/**
	 * Hook callback for `frm_check_file_referer`.
	 *
	 * Skips the HTTP referer check when the request carries a valid gated content
	 * access token. This allows token holders arriving directly from an email link
	 * (no Referer header) to still download protected files.
	 *
	 * @param bool $check_referer Whether to perform the referer check.
	 *
	 * @return bool
	 */
	public static function maybe_skip_file_referer_check( $check_referer ) {
		if ( ! $check_referer ) {
			return false;
		}

		$file_id = self::get_file_id_from_request();

		$file_item = FrmGatedItem::make(
			array(
				'type' => 'frm_file',
				'id'   => $file_id,
			)
		);

		if ( $file_id && null !== FrmGatedTokenHelper::get_valid_token( $file_item ) ) {
			return false;
		}

		return $check_referer;
	}

	/**
	 * Hook callback for `frm_can_access_protected_file`.
	 *
	 * Grants access to a role-protected file when the request carries a valid
	 * gated content token for that attachment. This runs before the role check
	 * in FrmProFileField::get_download_filepath() so token holders can bypass
	 * the login/role requirement.
	 *
	 * @param bool $can_access Whether access is granted so far.
	 * @param int  $file_id    WP attachment post ID.
	 *
	 * @return bool
	 */
	public static function can_access_protected_file( $can_access, $file_id ) {
		if ( $can_access ) {
			return true; // Already granted by another filter.
		}

		$file_item = FrmGatedItem::make(
			array(
				'type' => 'frm_file',
				'id'   => $file_id,
			)
		);
		return null !== FrmGatedTokenHelper::get_valid_token( $file_item );
	}

	/**
	 * Resolve the attachment ID for the file in the current URL.
	 *
	 * Extracts the attachment ID from the /frm_file/ payload in REQUEST_URI.
	 *
	 * @return int Attachment post ID, or 0 when unresolvable.
	 */
	private static function get_file_id_from_request() {
		$payload = FrmProFileField::get_file_payload();

		if ( ! $payload ) {
			return 0;
		}

		$decoded = base64_decode( $payload );
		$split   = preg_split( '/[:|]+/', (string) $decoded );

		if ( count( $split ) < 2 || 'id' !== $split[0] ) {
			return 0;
		}

		return (int) $split[1];
	}

	// ── Access page redirect ──────────────────────────────────────────────── //
	/**
	 * Redirect to the configured access page when a gated item is accessed without a valid token.
	 *
	 * Hooked to `frm_obtain_gated_token`. Fires after URL param, cookies, and user DB
	 * have all been checked without finding a valid token.
	 *
	 * @param FrmGatedToken|null        $token Null — no valid token found by core.
	 * @param array $args {
	 *
	 *     @type FrmGatedItem $item Content item being accessed.
	 * }
	 */
	public static function maybe_redirect_to_access_page( $token, $args ) {
		if ( null !== $token || is_admin() || wp_doing_ajax() ) {
			return $token;
		}

		$item = $args['item'];

		if ( ! $item->id || ! $item->type ) {
			return null;
		}

		self::find_action_id_with_access_page( $item );
		return null;
	}

	/**
	 * Find the first published gated content action that contains a given item
	 * and has an access_page_id configured. Redirects to the access page and exits
	 * if found, or returns null when no matching action exists.
	 *
	 * @param FrmGatedItem $item Content item to search for.
	 */
	private static function find_action_id_with_access_page( FrmGatedItem $item ) {
		$rows = self::get_published_gated_content_action_rows();

		foreach ( $rows as $row ) {
			$settings = FrmAppHelper::maybe_json_decode( $row->post_content );

			if ( empty( $settings['access_page_id'] ) || empty( $settings['items'] ) ) {
				continue;
			}

			if ( ! self::action_settings_contain_item( $settings, $item ) ) {
				continue;
			}

			$access_page_url = self::get_access_page_url( (int) $row->ID );

			if ( $access_page_url ) {
				wp_safe_redirect( $access_page_url );
				exit;
			}
		}

		return null;
	}

	/**
	 * Query all published gated content action rows from the database.
	 *
	 * @return object[]
	 */
	private static function get_published_gated_content_action_rows() {
		global $wpdb;

		return FrmDb::get_results(
			$wpdb->posts,
			array(
				'post_type'    => 'frm_form_actions',
				'post_excerpt' => FrmGatedContentAction::$slug,
				'post_status'  => 'publish',
			),
			'ID, post_content'
		);
	}

	/**
	 * Check whether decoded action settings contain an item matching the given FrmGatedItem.
	 *
	 * @param array        $settings Decoded action post_content settings array.
	 * @param FrmGatedItem $item     Item to search for.
	 *
	 * @return bool
	 */
	private static function action_settings_contain_item( $settings, FrmGatedItem $item ) {
		foreach ( $settings['items'] as $item_data ) {
			if ( $item->matches( $item_data ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build the access page URL for a gated content action.
	 *
	 * @param int $action_id Gated content action post ID.
	 *
	 * @return string Access page URL, or '' if not configured.
	 */
	public static function get_access_page_url( $action_id ) {
		if ( ! $action_id ) {
			return '';
		}

		$action = get_post( $action_id );

		if ( ! $action ) {
			return '';
		}

		$settings = FrmAppHelper::maybe_json_decode( $action->post_content );
		$page_id  = ! empty( $settings['access_page_id'] ) ? (int) $settings['access_page_id'] : 0;

		return $page_id ? (string) get_permalink( $page_id ) : '';
	}
}
