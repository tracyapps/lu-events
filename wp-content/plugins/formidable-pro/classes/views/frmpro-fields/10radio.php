<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$field['options'] = $this->get_options( array() );

if ( is_array( $field['options'] ) ) {
	if ( ! isset( $field['value'] ) ) {
		$field['value'] = $field['default_value'];
		FrmAppHelper::unserialize_or_decode( $field['value'] );
	}

	$last_key = array_key_last( $field['options'] );
	$prepend  = FrmField::get_option( $field, 'prepend' );
	$append   = FrmField::get_option( $field, 'append' );

	?>
	<div class="frm_scale_options">
		<?php
		foreach ( $field['options'] as $opt_key => $opt ) {
			$opt  = apply_filters( 'frm_field_label_seen', $opt, $opt_key, $field );
			$last = $opt_key === $last_key ? ' frm_last' : '';
			?>
			<div class="frm_scale <?php echo esc_attr( $last ); ?>"><label for="<?php echo esc_attr( $html_id . '-' . $opt_key ); ?>">
					<input type="radio" name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $html_id . '-' . $opt_key ); ?>" value="<?php echo esc_attr( $opt ); ?>" <?php
					checked( $field['value'], $opt ) . ' ';
					do_action( 'frm_field_input_html', $field );
					?> />
					<?php
					$field_obj = FrmFieldFactory::get_field_object( $field['id'] );
					$field_obj->echo_option_label( $opt );
					?></label></div>
			<?php
		}
		?>
		<div style="clear:both;"></div>
	</div>
	<?php
	if ( $prepend || $append ) {
		?>
		<div class="frm_scale_labels">
			<?php if ( $prepend ) { ?>
				<div class="frm_scale_label frm_scale_before"><?php FrmAppHelper::kses_echo( $prepend, array( 'i' ) ); ?></div>
			<?php } ?>
			<?php if ( $append ) { ?>
				<div class="frm_scale_label frm_scale_after"><?php FrmAppHelper::kses_echo( $append, array( 'i' ) ); ?></div>
			<?php } ?>
		</div>
		<?php
	}
}
?>
