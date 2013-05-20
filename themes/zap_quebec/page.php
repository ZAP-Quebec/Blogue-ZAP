<?php

?>

<?php get_header(); ?>
	<section class="page">
		<h2 class="title_tsz">Tout sur ZAP Québec</h2>
		<div class="liste">
			<?php if (have_posts()) : ?>
			<?php while (have_posts()) : the_post(); ?>
			<h3><?php the_title(); ?></h3>
				<?php the_content(); ?>
				
			<?php endwhile; ?>
			<?php else : ?>

					<h2 class="center">Aucun résultat</h2>
					<p class="center">Vous avez demandé quelque chose que nous n'avons pas. Désolé</p>
			<?php endif; ?>
		</div>
		<aside id="sidebar">
		<?php 	/* Widgetized sidebar, if you have the plugin installed. */
		if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar("side-page") ) : ?>
			<ul id="liste_pages">
				<li id="recherche">
					<? include("searchform.php"); ?>
				</li>
				<?php zapqc_get_page_list(); ?>
			</ul>
		<?php endif; ?>
		</aside>		
	</section>
<?php get_footer(); ?>


