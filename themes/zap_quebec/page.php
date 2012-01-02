<?php

?>

<?php get_header(); ?>
		<h3 id="title_tsz"></h3>
		<div id="tout_sur_zap" class="graybox">
			<div id="haut"></div>
			<div id="bas"></div>
			
			<div id="text_content">
			<?php if (have_posts()) : ?>
			<?php while (have_posts()) : the_post(); ?>
			<h1><?php the_title(); ?></h1>
				<?php the_content(); ?>
				
			<?php endwhile; ?>
			<?php else : ?>

					<h2 class="center">Aucun résultat</h2>
					<p class="center">Vous avez demandé quelque chose que nous n'avons pas. Désolé</p>
		

				<?php endif; ?><?php edit_post_link('<br />MODIFIER','',''); ?>
			</div>
			<div id="sidebar">
			
			<ul id="liste_pages">
				<li id="recherche">
					<? include("searchform.php"); ?>
				</li>
				<?php zapqc_get_page_list(); ?>
			</ul>
			</div>
			<div class="clearer"></div>
		</div>
		
		
		
<?php get_footer(); ?>


