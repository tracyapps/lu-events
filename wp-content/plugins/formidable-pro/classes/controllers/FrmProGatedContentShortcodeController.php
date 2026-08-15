<?php
/**
 * Pro Gated Content Shortcode Controller
 *
 * Handles Pro-side shortcode output for [frm_gated_content]:
 * expiry time display, frm_file item URLs and titles, and the
 * expiry shortcode rows added to the action settings reference table.
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

class FrmProGatedContentShortcodeController {

	/**
	 * Append expiry-related shortcode rows to the gated content reference table.
	 *
	 * Hooked to `frm_gated_content_shortcodes` filter. Rows are only appended when
	 * the action has an expiry configured (expired_hours set), since both shortcodes
	 * output nothing for tokens that never expire.
	 *
	 * @param array<int, array{code: string, output: string}> $shortcodes Existing shortcode rows.
	 * @param int                                             $action_id  Gated content action post ID.
	 *
	 * @return array<int, array{code: string, output: string}>
	 */
	public static function add_expiry_shortcode_rows( $shortcodes, $action_id ) {
		$action = get_post( $action_id );

		if ( ! $action ) {
			return $shortcodes;
		}

		$settings = FrmAppHelper::maybe_json_decode( $action->post_content );

		if ( empty( $settings['expired_hours'] ) ) {
			return $shortcodes;
		}

		$shortcodes[] = array(
			'code'   => '[frm_gated_content id="' . absint( $action_id ) . '" show="expired_time"]',
			'output' => __( 'Time until access expires (e.g. "1 day", "3 days")', 'formidable-pro' ),
		);

		return $shortcodes;
	}

	/**
	 * Handle the show="expired_time" attribute for [frm_gated_content].
	 *
	 * Hooked to `frm_gated_content_shortcode_custom_output`. Returns a human-readable
	 * string representing the configured access duration for the action (e.g. "1 day",
	 * "3 days") via human_time_diff(). Returns an empty string when no expiry is
	 * configured, or null to pass through when the show= value is not "expired_time".
	 *
	 * @param string|null $output Current output (null = not yet handled).
	 * @param array       $atts   Full shortcode attributes array (id, show, item, …).
	 *                            $atts['id'] is the gated content action post ID.
	 *
	 * @return string|null Human-readable duration string, empty string when unavailable, or null to pass through.
	 */
	public static function shortcode_show_expiry( $output, $atts ) {
		if ( 'expired_time' !== $atts['show'] ) {
			return $output;
		}

		$action = get_post( (int) $atts['id'] );

		if ( ! $action ) {
			return '';
		}

		$settings      = FrmAppHelper::maybe_json_decode( $action->post_content );
		$expired_hours = ! empty( $settings['expired_hours'] ) ? (int) $settings['expired_hours'] : 0;

		if ( ! $expired_hours ) {
			return '';
		}

		$now = time();
		return human_time_diff( $now, $now + $expired_hours * HOUR_IN_SECONDS );
	}
}
