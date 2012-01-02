<?php
/*
 Template Name: Page Nomade
*/ 
?>

<?php get_header(); ?>

			
			<div id="text_content" class="featured">
			<?php if (have_posts()) : ?>
			<?php while (have_posts()) : the_post(); ?>
			<h1 id="title_nomade"></h1>
				<?php the_content(); ?>
				
			<?php endwhile; ?><?php edit_post_link('<br />MODIFIER','',''); ?>
			<?php else : ?>

					<h2 class="center">Aucun résultat</h2>
					<p class="center">Vous avez demandé quelque chose que nous n'avons pas. Désolé</p>
		

				<?php endif; ?>
			</div>
		
			<div id="btn_contact"><a href="mailto:info@zapquebec.orgr"></a></div>
		
<?php get_footer(); ?>
