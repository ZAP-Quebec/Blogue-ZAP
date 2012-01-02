<?php get_header(); 

$latest_posts = get_posts(3);
?>
		<h3 id="der_nouv"><a href="<?php bloginfo('rss2_url'); ?>" id="derniere_nouv"></a></h3>
		<div id="news_container" class="graybox">
			<div id="haut"></div>
			<div id="bas"></div>
			
			<div id="text_content">
		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
		<div class="post" id="post-<?php the_ID(); ?>">
			<h1><?php the_title(); ?></h1>

			<div class="entry">
				<?php the_content('<p class="serif">Read the rest of this entry &raquo;</p>'); ?>

				<?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>

				<p class="postmetadata alt">
					<?php zapqc_get_description_line() ?>
				</p>

			</div>
		</div>
			<?php endwhile; else: ?>

		<p>Désolé aucun article ne correspond à votre demande</p>

		<?php endif; ?>
	</div>
		<div id="sidebar">
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
		</div>
	<div class="clearer"></div>
	</div>
	<?php comments_template(); ?>


<?php get_footer(); ?>
