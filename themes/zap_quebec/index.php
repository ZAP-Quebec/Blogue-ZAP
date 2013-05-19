<?php get_header(); ?>

		<h3></h3>
                
		<section class="nouvelles">
			<div class="liste">
				<?php if (have_posts()) : ?>

					<?php while (have_posts()) : the_post(); ?>

						<article class="post" id="post-<?php the_ID(); ?>">
							<h3><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
                            <?php zapqc_get_description_line();?>                          

							<div class="entry">
								<?php the_content('Read the rest of this entry &raquo;'); ?>
							</div>

						</acticle>

					<?php endwhile; ?>
				<?php else : ?>

					<h2 class="center">Erreur - Page vide</h2>
					<p class="center">Nous n'avons rien à afficher qui correspondre à votre demande</p>
		

				<?php endif; ?>
			</div>
			
			<?php get_sidebar(); ?>
		
		</section>


	<?php zapqc_get_prev_suiv(); ?>

<?php get_footer(); ?>
