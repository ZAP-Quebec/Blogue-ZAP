<?php
/*
 Template Name: Page espagnole
*/
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

					<h2 class="center">No result found</h2>
					<p class="center">You asked for something we don't have. Sorry.'</p>
		

				<?php endif; ?><?php edit_post_link('<br />MODIFIER','',''); ?>
			</div>
			<div id="sidebar">
			
			<ul id="liste_pages">
				<li id="recherche">
					<? include("searchform.php"); ?>
				</li>
				<?php wp_list_pages(array('title_li'=>'','meta_key'=>'langage','meta_value'=>'ES')); ?>
			</ul>
			</div>
			<div class="clearer"></div>
		</div>
		
		
		
<?php get_footer(); ?>
