	<div id="sidebar">
		<ul>
			<?php 	/* Widgetized sidebar, if you have the plugin installed. */
					if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) : ?>
			<li>
				<?php include (TEMPLATEPATH . '/searchform.php'); ?>
			</li>

			<?php wp_list_pages('title_li=<h2>Pages</h2>&include=4,6,251,276,272,246,971&sort_column=menu_order' ); ?>

			<?php wp_list_categories('show_count=1&title_li=<h2>Categories</h2>'); ?>

			<?php if ( is_404() || is_category() || is_day() || is_month() ||
						is_year() || is_search() || is_paged() ) {
			?> <li>

			<?php /* If this is a 404 page */ if (is_404()) { ?>
			<?php /* If this is a category archive */ } elseif (is_category()) { ?>
			
			<?php /* If this is a monthly archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
			<p>Vous naviguez présentement l'archive <a href="<?php echo bloginfo('url'); ?>/"><?php echo bloginfo('name'); ?></a> </p>

			<?php } ?>

			</li> <?php }?>


			<li class="cat_list"><h2>Archives</h2>
				<ul>
				<?php wp_get_archives('type=monthly'); ?>
				</ul>
			</li>

			<?php endif; ?>
		</ul>
	</div>

