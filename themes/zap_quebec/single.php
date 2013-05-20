<?php get_header(); 

$latest_posts = get_posts(3);
?>
	<section class="nouvelle">
		<div class="liste">
		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
			<article class="post" id="post-<?php the_ID(); ?>">
				<h3><?php the_title(); ?></h3>
				<?php zapqc_get_description_line() ?>

				<div class="entry">
					<?php the_content('<p class="serif">Read the rest of this entry &raquo;</p>'); ?>

					<?php comments_template(); ?>

					<?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>

				</div>
			</article>
		<?php endwhile; else: ?>
			<article>
				<p>Désolé aucun article ne correspond à votre demande</p>
			</article>
		<?php endif; ?>
		</div>
		<aside id="sidebar">
			<?php 	/* Widgetized sidebar, if you have the plugin installed. */
					if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar("side-news") ) : ?>
			<div>
				<?php include (TEMPLATEPATH . '/searchform.php'); ?>
			</div>
			<h2 class="cat-item-standalone"><a href="/dossier-de-presse/">Dossier de presse</a></h2>
				<ul id="news_feed">
					<li><a href="<?php echo $latest_posts[0]->guid; ?>"><h4 class="news"><?php echo $latest_posts[0]->post_title; ?></h4></a>
					<p> <?php echo snippet_text($latest_posts[0]->post_content, 20)?></p></li>
					<li><a href="<?php echo $latest_posts[1]->guid; ?>"><h4 class="news"><?php echo $latest_posts[1]->post_title; ?></h4></a>
					<p><?php echo snippet_text($latest_posts[1]->post_content, 20)?></p></li>
					<li><a href="<?php echo $latest_posts[2]->guid; ?>"><h4 class="news"><?php echo $latest_posts[2]->post_title; ?></h4></a>
					<p><?php echo snippet_text($latest_posts[2]->post_content, 20)?></p></li>
				</ul>
			<?php endif; ?>
		</aside>
	</section>

<?php get_footer(); ?>
