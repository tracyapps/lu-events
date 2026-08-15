<?php
get_header();
the_post();
$slug = get_post_meta( get_the_ID(), '_lu_shared_page_slug', true ) ?: get_post_field( 'post_name', get_the_ID() );
$shared = class_exists( 'LU_Event_Network' ) ? LU_Event_Network::get_shared_page( $slug ) : null;
?>
<main id="main-content" class="policy-page">
	<div class="policy-page__inner">
		<p class="eyebrow"><?php esc_html_e( 'Loyalty Untapped event sites', 'lu-event' ); ?></p>
		<h1><?php echo esc_html( $shared['title'] ?? get_the_title() ); ?></h1>
		<div class="policy-page__content">
			<?php echo apply_filters( 'the_content', $shared['content'] ?? get_the_content() ); ?>
		</div>
		<?php if ( ! empty( $shared['modified'] ) ) : ?><p class="policy-page__updated"><?php echo esc_html( 'Last updated ' . mysql2date( get_option( 'date_format' ), $shared['modified'] ) ); ?></p><?php endif; ?>
	</div>
</main>
<?php get_footer(); ?>
