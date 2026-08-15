<?php
/**
 * Pro Gated Content form action
 *
 * Extends Lite's FrmGatedContentAction with:
 * - frm_file and view item types
 * - payment-success event
 * - expired_hours settings UI
 *
 * @package Formidable Pro
 *
 * @since 6.33
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProGatedContentAction extends FrmGatedContentAction {

	/**
	 * Per-request cache for get_files_by_form().
	 *
	 * @var array|null
	 */
	private static $files_cache;

	/**
	 * Per-request cache for get_pages().
	 *
	 * @var WP_Post[]|null
	 */
	private static $pages_cache;

	/**
	 * Add the 'update' event to the gated content action (Pro only).
	 *
	 * Hooked to `frm_gated_content_control_settings` so Lite's constructor does
	 * not expose the update event in the free plugin.
	 *
	 * @param array $action_ops Action options array from FrmGatedContentAction::__construct().
	 *
	 * @return array
	 */
	public static function add_update_event( $action_ops ) {
		if ( ! in_array( 'update', $action_ops['event'], true ) ) {
			$action_ops['event'][] = 'update';
		}
		return $action_ops;
	}

	/**
	 * Replace the Lite gated_content action class with this Pro class.
	 *
	 * Hooked to `frm_registered_form_actions` at priority 20, after Lite registers
	 * at default priority (10).
	 *
	 * @param array $actions Registered action classes keyed by slug.
	 *
	 * @return array
	 */
	public static function register_pro_action( $actions ) {
		$actions[ FrmGatedContentAction::$slug ] = self::class;
		return $actions;
	}

	/**
	 * Render the action settings form (Lite view + Pro additions).
	 *
	 * @param object $instance Form action post object.
	 * @param array  $args     Contains `form`, `action_key`, `values`.
	 */
	public function form( $instance, $args = array() ) {
		parent::form( $instance, $args );

		// Pro-only action-level settings (expiry, etc.).
		$frm_gc_action     = $instance;
		$frm_gc_action_ops = $this;
		include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-form-actions/_gated_content_pro_settings.php';
	}

	/**
	 * Render Pro type-specific settings for a gated content item row.
	 *
	 * Hooked to `frm_gated_content_item_type_settings` at priority 10.
	 *
	 * @param array $args {
	 *
	 *     @type bool   $is_template True when rendering inside the JS <template> element.
	 *     @type string $active_type Active type key for this item (existing rows only).
	 *     @type int    $idx         Zero-based item index (existing rows only).
	 *     @type array  $item        Saved item data (existing rows only).
	 *     @type string $item_base   Field name prefix for this item (existing rows only).
	 *     @type string $wrapper_id  Unique wrapper element ID (existing rows only).
	 * }
	 */
	public static function render_item_type_settings( $args ) {
		$is_template = ! empty( $args['is_template'] );
		$active_type = '';
		$idx         = 0;
		$item        = array();
		$item_base   = '';
		$wrapper_id  = '';

		if ( ! $is_template ) {
			$active_type = $args['active_type'];
			$idx         = $args['idx'];
			$item        = $args['item'];
			$item_base   = $args['item_base'];
			$wrapper_id  = $args['wrapper_id'];
		}

		include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-form-actions/_gated_content_file_item_settings.php';
	}

	/**
	 * Sanitize a gated content item, adding form_id for frm_file type.
	 *
	 * Hooked to `frm_gated_content_sanitize_item`.
	 *
	 * @param array $item Sanitized item data (type, id).
	 * @param array $args {
	 *
	 *     @type array $raw_item Raw submitted item data.
	 * }
	 *
	 * @return array
	 */
	public static function sanitize_item( $item, $args ) {
		if ( 'frm_file' === $item['type'] ) {
			$raw_item        = $args['raw_item'];
			$item['form_id'] = isset( $raw_item['form_id'] ) ? absint( $raw_item['form_id'] ) : 0;
		}

		return $item;
	}

	/**
	 * Get all Formidable-uploaded files from entries, grouped by form ID.
	 *
	 * @return array{files: array<int, array<int, string>>, form_names: array<int, string>}
	 */
	public static function get_files_by_form() {
		if ( null !== self::$files_cache ) {
			return self::$files_cache;
		}

		$form_names = self::get_form_names_with_file_fields();

		self::$files_cache = array(
			'files'      => self::get_grouped_files( self::get_attachment_ids_by_form() ),
			'form_names' => $form_names,
		);

		return self::$files_cache;
	}

	/**
	 * Return a map of form ID → form name for every form that has a file field.
	 *
	 * @return array<int, string> [ form_id => form_name ]
	 */
	private static function get_form_names_with_file_fields() {
		$form_names  = array();
		$file_fields = FrmField::getAll(
			array( 'type' => 'file' ),
			'field_order'
		);

		foreach ( $file_fields as $field ) {
			$form_id = (int) $field->form_id;

			if ( isset( $form_names[ $form_id ] ) ) {
				continue;
			}

			$form = FrmForm::getOne( $form_id );
			// translators: %d: Form ID.
			$form_names[ $form_id ] = $form ? FrmProFormsHelper::get_form_name( $form ) : sprintf( __( 'Form #%d', 'formidable-pro' ), $form_id );
		}

		return $form_names;
	}

	/**
	 * Return uploaded attachment IDs from entry metas, grouped by form ID.
	 *
	 * Single-file fields store a plain integer string ("123").
	 * Multi-file fields store a PHP-serialized array.
	 *
	 * @return array<int, array<int, true>> [ form_id => [ att_id => true ] ]
	 */
	private static function get_attachment_ids_by_form() {
		global $wpdb;

		$cache_key   = 'frm_gc_att_ids_by_form';
		$cache_group = 'frm_gated_content';
		$cached      = wp_cache_get( $cache_key, $cache_group );

		if ( false !== $cached ) {
			return $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT im.meta_value, i.form_id
				 FROM %i im
				 INNER JOIN %i f ON f.id = im.field_id AND f.type = \'file\'
				 INNER JOIN %i i ON i.id = im.item_id
				 WHERE im.meta_value != \'\' AND im.meta_value != \'0\'',
				$wpdb->prefix . 'frm_item_metas',
				$wpdb->prefix . 'frm_fields',
				$wpdb->prefix . 'frm_items'
			)
		);

		$att_ids_by_form = array();

		foreach ( $rows as $row ) {
			$form_id = (int) $row->form_id;

			if ( ! $form_id ) {
				continue;
			}

			foreach ( (array) FrmAppHelper::maybe_unserialize_array( $row->meta_value ) as $att_id ) {
				$att_id = (int) $att_id;

				if ( $att_id ) {
					$att_ids_by_form[ $form_id ][ $att_id ] = true;
				}
			}
		}

		wp_cache_set( $cache_key, $att_ids_by_form, $cache_group );

		return $att_ids_by_form;
	}

	/**
	 * Resolve attachment IDs to filenames and group them by form ID.
	 *
	 * Fetches all attachment rows in a single query, then maps each ID to its
	 * display filename (basename of guid, falling back to post_title).
	 *
	 * @param array<int, array<int, true>> $att_ids_by_form [ form_id => [ att_id => true ] ]
	 *
	 * @return array<int, array<int, string>> [ form_id => [ att_id => filename ] ]
	 */
	private static function get_grouped_files( $att_ids_by_form ) {
		if ( ! $att_ids_by_form ) {
			return array();
		}

		global $wpdb;

		$all_att_ids = array();

		foreach ( $att_ids_by_form as $att_id_map ) {
			foreach ( array_keys( $att_id_map ) as $att_id ) {
				$all_att_ids[ $att_id ] = true;
			}
		}

		$placeholders = implode( ',', array_fill( 0, count( $all_att_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$attachment_rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT ID, post_title, guid FROM %i WHERE ID IN (' . $placeholders . ') AND post_type = \'attachment\' ORDER BY post_title ASC',
				$wpdb->posts,
				...array_keys( $all_att_ids )
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		$att_by_id = array();

		foreach ( $attachment_rows as $row ) {
			$att_by_id[ (int) $row->ID ] = $row;
		}

		$grouped = array();

		foreach ( $att_ids_by_form as $form_id => $att_id_map ) {
			foreach ( array_keys( $att_id_map ) as $att_id ) {
				if ( ! isset( $att_by_id[ $att_id ] ) ) {
					continue;
				}

				$att                            = $att_by_id[ $att_id ];
				$frm_gc_basename                = basename( (string) $att->guid );
				$frm_gc_title                   = (string) $att->post_title;
				$grouped[ $form_id ][ $att_id ] = $frm_gc_basename !== '' ? $frm_gc_basename : ( $frm_gc_title !== '' ? $frm_gc_title : __( '(untitled)', 'formidable-pro' ) );
			}
		}

		return $grouped;
	}

	/**
	 * Get all published WordPress pages, ordered by title.
	 *
	 * @return WP_Post[]
	 */
	public static function get_pages() {
		if ( null !== self::$pages_cache ) {
			return self::$pages_cache;
		}

		/** @var false|WP_Post[] $pages */
		$pages             = get_pages(
			array(
				'post_status' => 'publish',
				'sort_column' => 'post_title',
				'sort_order'  => 'ASC',
			)
		);
		self::$pages_cache = is_array( $pages ) ? $pages : array();

		return self::$pages_cache;
	}
}
