<?php
/*
 Template Name: Page Zapper
*/ 
?>

<?php get_header(); ?>

			
			<div id="text_content" class="featured">
			<?php if (have_posts()) : ?>
			<?php while (have_posts()) : the_post(); ?>
			<h1 id="title_duz"></h1>
				<?php the_content(); ?>
				
			<?php endwhile; ?><?php edit_post_link('<br />MODIFIER','',''); ?>
			<?php else : ?>

					<h2 class="center">Aucun résultat</h2>
					<p class="center">Vous avez demandé quelque chose que nous n'avons pas. Désolé</p>
		

				<?php endif; ?>
			</div>
		
		
			<div id="btn_contact"><a href="mailto:info@zapquebec.org"></a></div>
            <div class="clearer"></div>
<?php get_footer(); ?>
