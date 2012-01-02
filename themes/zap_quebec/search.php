<?php get_header(); ?>

		<h2 id="search_title" class="pagetitle"></h2>

		<div class="graybox" id="search_results_content">
			<div id="haut"></div>
			<div id="bas"></div>
			
			<div id="text_content">
		<?php if (have_posts()) : ?>
		<?php while (have_posts()) : the_post(); ?>

			<div class="post">
				<h1 id="post-<?php the_ID(); ?>"><a href="<?php the_permalink() ?>" rel="bookmark" title="Lien permanent vers <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h1>
				
				<?php the_excerpt(); zapqc_get_description_line(); ?>
			</div>

		<?php endwhile; ?>

		<?php else : ?>

		<h2 class="center">Votre recherche n'a retourné aucun résultats.</h2>


		<?php endif; ?>

		</div>
	<?php get_sidebar(); ?>
	<div class="clearer"></div>
	</div>

	<?php zapqc_get_prev_suiv(); ?>
<?php get_footer(); ?>
