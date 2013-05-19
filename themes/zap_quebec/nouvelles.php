<?php
/*
Template Name: Nouvelles
*/
?>

<?php get_header(); ?>

		<h3>Les nouvelles</h3>
		<section class="nouvelles">
			<div class="liste">
				<?php if (have_posts()) : ?>

					<?php while (have_posts()) : the_post(); ?>

						<article class="post" id="post-<?php the_ID(); ?>">
							<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>

							<div class="entry">
								<?php the_content('Read the rest of this entry &raquo;'); ?>
							</div>

							<?php zapqc_get_description_line() ?>
						</article>

					<?php endwhile; ?>
				<?php else : ?>

					<p class="title">Aucun résultat</p>
					<p class="center">Vous chercher qelque chose que nous n'avons pas. Désoler</p>
		

				<?php endif; ?>
			
			</div>
			<aside>
				<?php get_sidebar(); ?>
			</aside>
		</section>


	<?php zapqc_get_prev_suiv() ?>

<?php get_footer(); ?>
