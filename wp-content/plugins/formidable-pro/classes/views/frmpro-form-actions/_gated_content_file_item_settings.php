<?php
/**
 * Gated Content — frm_file type settings
 *
 * Shared by both the existing item row and the JS-cloned <template> row.
 * Set $is_template = true before including when rendering inside <template>.
 *
 * Template mode differences:
 * - Wrapper always gets the `hidden` attribute.
 * - Labels use data-frm-gc-for instead of for.
 * - Selects have no id or name attributes.
 * - Options have no selected state or display:none filtering.
 *
 * @package Formidable Pro
 *
 * @since 6.33
 *
 * @var string $active_type Active type key for this item (existing rows only).
 * @var int    $idx         Zero-based item index (existing rows only).
 * @var array  $item        Saved item data — type, id, form_id (existing rows only).
 * @var string $item_base   Field name prefix for this item (existing rows only).
 * @var string $wrapper_id  Unique wrapper element ID (existing rows only).
 * @var bool   $is_template True when rendering inside the JS <template> element.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$is_template  = ! empty( $is_template );
$file_data    = FrmProGatedContentAction::get_files_by_form();
$item_form_id = $is_template ? 0 : ( isset( $item['form_id'] ) ? (int) $item['form_id'] : 0 );
$item_id      = $is_template ? 0 : ( isset( $item['id'] ) ? (int) $item['id'] : 0 );
$form_sel_id  = $is_template ? '' : $wrapper_id . '_form_id_frm_file_' . $idx;
$file_sel_id  = $is_template ? '' : $wrapper_id . '_id_frm_file_' . $idx;
$hide_wrapper = $is_template || 'frm_file' !== $active_type;

$wrapper_atts = array(
	'class'     => 'frm-gc-type-settings',
	'data-type' => 'frm_file',
);

if ( $hide_wrapper ) {
	$wrapper_atts['hidden'] = '';
}
?>

<div <?php FrmAppHelper::array_to_html_params( $wrapper_atts, true ); ?>>
	<div class="frm_form_field frm-mt-xs frm-mb-xs">
		<?php
		$form_label_atts = $is_template
			? array( 'data-frm-gc-for' => 'form_id' )
			: array( 'for' => $form_sel_id );
		?>
		<label <?php FrmAppHelper::array_to_html_params( $form_label_atts, true ); ?>>
			<?php esc_html_e( 'Form', 'formidable' ); ?>
		</label>
		<?php
		$form_sel_atts = array(
			'data-frm-gc-field' => 'form_id',
			'class'             => 'frm-gc-file-form-select',
		);

		if ( ! $is_template ) {
			$form_sel_atts['id'] = $form_sel_id;

			if ( 'frm_file' === $active_type ) {
				$form_sel_atts['name'] = $item_base . '[form_id]';
			}
		}
		?>
		<select <?php FrmAppHelper::array_to_html_params( $form_sel_atts, true ); ?>>
			<option value=""><?php esc_html_e( '— Select a form —', 'formidable-pro' ); ?></option>
			<?php foreach ( $file_data['form_names'] as $fid => $fname ) : ?>
				<option
					value="<?php echo esc_attr( $fid ); ?>"
					<?php if ( ! $is_template ) : ?>
						<?php selected( $item_form_id, $fid ); ?>
					<?php endif; ?>
				>
					<?php echo esc_html( $fname ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div><!-- .frm_form_field -->

	<div class="frm_form_field frm-mt-xs frm-mb-xs">
		<?php
		$file_label_atts = $is_template
			? array( 'data-frm-gc-for' => 'id' )
			: array( 'for' => $file_sel_id );
		?>
		<label <?php FrmAppHelper::array_to_html_params( $file_label_atts, true ); ?>>
			<?php esc_html_e( 'File', 'formidable-pro' ); ?>
		</label>
		<?php
		$file_sel_atts = array( 'data-frm-gc-field' => 'id' );

		if ( ! $is_template ) {
			$file_sel_atts['id'] = $file_sel_id;

			if ( 'frm_file' === $active_type ) {
				$file_sel_atts['name'] = $item_base . '[id]';
			}
		}
		?>
		<select <?php FrmAppHelper::array_to_html_params( $file_sel_atts, true ); ?>>
			<option value=""><?php esc_html_e( '— Select a file —', 'formidable-pro' ); ?></option>
			<?php foreach ( $file_data['files'] as $fid => $ffiles ) : ?>
				<?php foreach ( $ffiles as $att_id => $filename ) : ?>
					<option
						value="<?php echo esc_attr( $att_id ); ?>"
						data-form-id="<?php echo esc_attr( $fid ); ?>"
						<?php if ( ! $is_template ) : ?>
							<?php selected( $item_id, $att_id ); ?>
							<?php if ( ! $item_form_id || $item_form_id !== $fid ) : ?>
								style="display:none"
							<?php endif; ?>
						<?php endif; ?>
					>
						<?php echo esc_html( $filename ); ?>
					</option>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</select>
	</div><!-- .frm_form_field -->
</div><!-- [data-type="frm_file"] -->

<?php
unset(
	$is_template,
	$file_data,
	$item_form_id,
	$item_id,
	$form_sel_id,
	$file_sel_id,
	$hide_wrapper,
	$wrapper_atts,
	$form_label_atts,
	$form_sel_atts,
	$file_label_atts,
	$file_sel_atts,
	$fid,
	$fname,
	$ffiles,
	$att_id,
	$filename
);
