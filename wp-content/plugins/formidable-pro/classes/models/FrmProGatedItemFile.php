<?php
/**
 * Gated Content File Item
 *
 * Represents a 'frm_file' type item in a gated content action.
 * Carries the form_id the file field belongs to, stored alongside the
 * attachment ID in the action settings.
 *
 * @package Formidable Pro
 *
 * @since 6.33
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProGatedItemFile extends FrmGatedItem {

	/**
	 * ID of the form that owns the file upload field.
	 *
	 * @var int
	 */
	public $form_id;

	/**
	 * @param array{type: string, id: int|string, form_id?: int} $item Item data array with 'type', 'id', and optional 'form_id' keys.
	 */
	public function __construct( array $item ) {
		parent::__construct( $item );
		$this->form_id = $item['form_id'] ?? 0;
	}

	/**
	 * Return the protected file download URL with the access token appended.
	 *
	 * @param string $raw_token Raw access token to append as the access_code query arg.
	 *
	 * @return string Download URL with access_code parameter, or empty string when unavailable.
	 */
	public function get_url( $raw_token ) {
		$url = wp_get_attachment_url( $this->id );
		return $url ? add_query_arg( 'access_code', $raw_token, $url ) : '';
	}
}
