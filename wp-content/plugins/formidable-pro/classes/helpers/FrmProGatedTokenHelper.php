<?php
/**
 * Pro Gated Token Helper
 *
 * @package FrmPro
 *
 * @since 6.33
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProGatedTokenHelper {

	/**
	 * Revoke all tokens for a specific action + entry pair.
	 *
	 * Called before re-generating a token on entry update so the previous
	 * token cannot be used after the entry owner receives a fresh one.
	 *
	 * @param int $action_id ID of the frm_form_actions post.
	 * @param int $entry_id  ID of the form entry.
	 *
	 * @return void
	 */
	public static function delete_by_action_and_entry( $action_id, $entry_id ) {
		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . 'frm_gated_tokens',
			array(
				'action_id' => $action_id,
				'entry_id'  => $entry_id,
			),
			array( '%d', '%d' )
		);
	}
}
