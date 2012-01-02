<?php get_header(); ?>

		<h3><a href="#" id="derniere_nouv"></a></h3>
		<div class="graybox">
			<div id="haut"></div>
			<div id="bas"></div>

			<div id="text_content">

		<?php if (have_posts()) : ?>

 	  <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
 	  <?php /* If this is a category archive */ if (is_category()) { ?>
		<h2 class="pagetitle">Archives pour la catégorie <?php single_cat_title(); ?></h2>
 	  <?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
		<h2 class="pagetitle">Articles avec le Tag &#8216;<?php single_tag_title(); ?>&#8217;</h2>
 	  <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
		<h2 class="pagetitle">Archive dater du <?php the_time('j F Y'); ?></h2>
 	  <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
		<h2 class="pagetitle">Archive pour <?php the_time('F, Y'); ?></h2>
 	  <?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
		<h2 class="pagetitle">Archives pour <?php the_time('Y'); ?></h2>
	  <?php /* If this is an author archive */ } elseif (is_author()) { ?>
		<h2 class="pagetitle">Archive par auteur</h2>
 	  <?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
		<h2 class="pagetitle">Archive du blog</h2>
 	  <?php } ?>

		<p></p>
		<?php while (have_posts()) : the_post(); ?>
		<div class="post">
				<h3 id="post-<?php the_ID(); ?>"><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>

				<div class="entry">
					<?php the_content() ?>
				</div>

				<?php zapqc_get_description_line(); ?>

			</div>

		<?php endwhile; ?>
		<?php else : ?>

			<h2 class="center">Aucun résultat</h2>
			<?php include (TEMPLATEPATH . '/searchform.php'); ?>

		<?php endif; ?>
			</div>
			<?php get_sidebar(); ?>
			<div class="clearer"></div>
		</div>		

	

	</div>

	<?php zapqc_get_prev_suiv(); ?>

<?php get_footer(); ?>
