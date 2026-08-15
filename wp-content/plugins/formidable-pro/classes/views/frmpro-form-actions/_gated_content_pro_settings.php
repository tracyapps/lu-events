<?php
/**
 * Pro Gated Content action settings — action-level Pro fields
 *
 * Rendered after the Lite view via FrmProGatedContentAction::form().
 *
 * @package Formidable Pro
 *
 * @since 6.33
 *
 * @var object                    $frm_gc_action     Form action post object.
 * @var FrmProGatedContentAction  $frm_gc_action_ops Action class instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$frm_gc_pro_expired_hours = $frm_gc_action->post_content['expired_hours'] ?? '';
$frm_gc_pro_expired_name  = $frm_gc_action_ops->get_field_name( 'expired_hours' );
$frm_gc_pro_expired_id    = $frm_gc_action_ops->get_field_id( 'expired_hours' );

$frm_gc_keep_token_id        = $frm_gc_action_ops->get_field_id( 'keep_token_on_update' );
$frm_gc_keep_token_name      = $frm_gc_action_ops->get_field_name( 'keep_token_on_update' );
$frm_gc_keep_token           = ! empty( $frm_gc_action->post_content['keep_token_on_update'] );
$frm_gc_pro_event_select_id  = $frm_gc_action_ops->get_field_id( 'event' );
$frm_gc_pro_event            = (array) ( $frm_gc_action->post_content['event'] ?? array() );
$frm_gc_pro_has_update_event = in_array( 'update', $frm_gc_pro_event, true );

$frm_gc_access_page_id       = ! empty( $frm_gc_action->post_content['access_page_id'] ) ? (int) $frm_gc_action->post_content['access_page_id'] : 0;
$frm_gc_access_page_name     = $frm_gc_action_ops->get_field_name( 'access_page_id' );
$frm_gc_access_page_field_id = $frm_gc_action_ops->get_field_id( 'access_page_id' );
$frm_gc_pages                = FrmProGatedContentAction::get_pages();
?>

<?php // ── Section: Expiry ─────────────────────────────────────────── ?>
<div class="frm_form_field frm_gc_expiry_section" style="margin-top: 20px;">
	<label for="<?php echo esc_attr( $frm_gc_pro_expired_id ); ?>">
		<?php esc_html_e( 'Access expires after (hours)', 'formidable-pro' ); ?>
	</label>
	<input
		type="number"
		id="<?php echo esc_attr( $frm_gc_pro_expired_id ); ?>"
		name="<?php echo esc_attr( $frm_gc_pro_expired_name ); ?>"
		value="<?php echo esc_attr( $frm_gc_pro_expired_hours ); ?>"
		min="1"
		step="1"
		placeholder="<?php esc_attr_e( 'Never', 'formidable-pro' ); ?>"
		class="frm-number-input"
	>
	<p class="frm_description">
		<?php esc_html_e( 'Leave blank for access that never expires.', 'formidable-pro' ); ?>
	</p>
</div><!-- .frm_gc_expiry_section -->

<?php // ── Section: Access Page ─────────────────────────────────────── ?>
<div class="frm_grid_container">
	<div class="frm6">
		<div class="frm_form_field frm_gc_access_page_section" style="margin-top: 20px;">
			<label for="<?php echo esc_attr( $frm_gc_access_page_field_id ); ?>">
				<?php esc_html_e( 'Access page', 'formidable-pro' ); ?>
			</label>
			<select
				id="<?php echo esc_attr( $frm_gc_access_page_field_id ); ?>"
				name="<?php echo esc_attr( $frm_gc_access_page_name ); ?>"
			>
				<option value="0"><?php esc_html_e( '— None —', 'formidable-pro' ); ?></option>
				<?php foreach ( $frm_gc_pages as $frm_gc_page ) : ?>
					<option value="<?php echo esc_attr( $frm_gc_page->ID ); ?>" <?php selected( $frm_gc_access_page_id, $frm_gc_page->ID ); ?>>
						<?php echo esc_html( $frm_gc_page->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="frm_description">
				<?php esc_html_e( 'Visitors without a valid token are redirected to this page. After submitting the form here, they are sent to the gated content with a new token.', 'formidable-pro' ); ?>
			</p>
		</div><!-- .frm_gc_access_page_section -->
	</div><!-- .frm6 -->
</div><!-- .frm_grid_container -->

<?php // ── Section: Update behavior ─────────────────────────────────── ?>
<div
	class="frm_form_field frm_gc_update_section"
	data-frm-gc-event-id="<?php echo esc_attr( $frm_gc_pro_event_select_id ); ?>"
	style="margin-top: 20px;"
	<?php echo $frm_gc_pro_has_update_event ? '' : 'hidden'; ?>
>
	<label>
		<input
			type="checkbox"
			id="<?php echo esc_attr( $frm_gc_keep_token_id ); ?>"
			name="<?php echo esc_attr( $frm_gc_keep_token_name ); ?>"
			value="1"
			<?php checked( $frm_gc_keep_token ); ?>
		>
		<?php esc_html_e( 'Keep old token when entry is updated', 'formidable-pro' ); ?>
	</label>
	<p class="frm_description">
		<?php esc_html_e( 'When checked, the original access token remains valid after the entry is updated. A new token is still generated and sent.', 'formidable-pro' ); ?>
	</p>
</div><!-- .frm_gc_update_section -->

<?php
unset( $frm_gc_action, $frm_gc_action_ops, $frm_gc_pro_expired_hours, $frm_gc_pro_expired_name, $frm_gc_pro_expired_id, $frm_gc_keep_token_id, $frm_gc_keep_token_name, $frm_gc_keep_token, $frm_gc_pro_event_select_id, $frm_gc_pro_event, $frm_gc_pro_has_update_event, $frm_gc_access_page_id, $frm_gc_access_page_name, $frm_gc_access_page_field_id, $frm_gc_pages, $frm_gc_page );
