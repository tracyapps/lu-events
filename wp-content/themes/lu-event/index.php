<?php
get_header();
?>
<main id="main-content" class="policy-page"><div class="policy-page__inner">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<article><h1><?php the_title(); ?></h1><?php the_content(); ?></article>
	<?php endwhile; else : ?>
		<h1><?php esc_html_e( 'Nothing here yet.', 'lu-event' ); ?></h1>
	<?php endif; ?>
</div></main>
<?php get_footer(); ?>
