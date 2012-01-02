<?php get_header(); ?>

		<h3><a href="<?php bloginfo('rss2_url'); ?>" id="derniere_nouv"></a></h3>
                
		<div class="graybox">
			<div id="haut"></div>
			<div id="bas"></div>

			<div id="text_content">
				<?php if (have_posts()) : ?>

					<?php while (have_posts()) : the_post(); ?>

						<div class="post" id="post-<?php the_ID(); ?>">
							<h1><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h1>
                                                      

							<div class="entry">
								<?php the_content('Read the rest of this entry &raquo;'); ?>
							</div>

							<?php zapqc_get_description_line();?>
						</div>

					<?php endwhile; ?>
				<?php else : ?>

					<h2 class="center">Erreur - Page vide</h2>
					<p class="center">Nous n'avons rien à afficher qui correspondre à votre demande</p>
		

				<?php endif; ?>
			
			</div>

			<?php get_sidebar(); ?>

			<div class="clearer"></div>
		</div>


	<?php zapqc_get_prev_suiv(); ?>

<?php get_footer(); ?>
