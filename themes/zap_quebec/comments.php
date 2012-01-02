<?php // Do not delete these lines
	if (!empty($_SERVER['SCRIPT_FILENAME']) && 'comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
		die ('Please do not load this page directly. Thanks!');

	if (!empty($post->post_password)) { // if there's a password
		if ($_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password) {  // and it doesn't match the cookie
			?>

			<p class="nocomments">This post is password protected. Enter the password to view comments.</p>

			<?php
			return;
		}
	}

	/* This variable is for alternating comment background */
	$oddcomment = 'class="alt" ';
?>

<!-- You can start editing here. -->
<h3 id="title_comment"><?php post_comments_feed_link($link_text= "&nbsp;") ?></a></h3>
<div id="haut_comment"></div>
<div id="comment_box">
<?php if ($comments) : ?>

	<ol class="commentlist">

	<?php foreach ($comments as $comment) : ?>

		<li <?php echo $oddcomment; ?>id="comment-<?php comment_ID() ?>">
				<?php echo get_avatar( $comment, 64 ); ?>
			<?php if ($comment->comment_approved == '0') : ?>
				<p class="mod_warning">Your comment is awaiting moderation.</p>
			<?php endif; ?>
			<div class="comment_text"><?php comment_text() ?></</div>
			
			<div class="signature"><cite>Par <?php comment_author_link() ?> le <?php comment_date('F jS, Y') ?> à <?php comment_time() ?></a> <?php edit_comment_link('edit','&nbsp;&nbsp;',''); ?></cite></div>
		</li>

	<?php
		/* Changes every other comment to a different class */
		$oddcomment = ( empty( $oddcomment ) ) ? 'class="alt" ' : '';
	?>

	<?php endforeach; /* end for each comment */ ?>

	</ol>

 <?php else : // this is displayed if there are no comments so far ?>

	<?php if ('open' == $post->comment_status) : ?>
		<!-- If comments are open, but there are no comments. -->

	 <?php else : // comments are closed ?>
		<!-- If comments are closed. -->
		<p class="nocomments">Comments are closed.</p>

	<?php endif; ?>
<?php endif; ?>


<?php if ('open' == $post->comment_status) : ?>

<div id="leave_comment">
<h3 id="respond">VOS COMMENTAIRES</h3>


<?php if ( get_option('comment_registration') && !$user_ID ) : ?>
<p>You must be <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php echo urlencode(get_permalink()); ?>">logged in</a> to post a comment.</p>
<?php else : ?>

<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="commentform">

<?php if ( $user_ID ) : ?>

<p>Logged in as <a href="<?php echo get_option('siteurl'); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>. <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?action=logout" title="Log out of this account">Log out &raquo;</a></p>

<?php else : ?>
<ul class="comment_info">

<li><input type="text" onclick="javascript:this.value = ''" name="author" id="author" value="NOM" size="22" tabindex="1" <?php if ($req) echo "aria-required='true'"; ?> /></li>

<li><input type="text" onclick="javascript:this.value = ''" name="email" id="email" value="COURRIEL"22" tabindex="2" <?php if ($req) echo "aria-required='true'"; ?> /</li>

<li><input type="text" onclick="javascript:this.value = ''" name="url" id="url" value="SITE WEB" size="22" tabindex="3" /></li>
</ul>
<?php endif; ?>

<!--<p><small><strong>XHTML:</strong> You can use these tags: <code><?php echo allowed_tags(); ?></code></small></p>-->

<div id="comment_cont"><textarea name="comment" id="comment" cols="100%" rows="10" tabindex="4"></textarea></div>

<p><input name="submit" type="submit" id="submit" tabindex="5" value="" />
<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
</p>
<?php do_action('comment_form', $post->ID); ?>

</form>
</div>
<?php endif; // If registration required and not logged in ?>

<?php endif; // if you delete this the sky will fall on your head ?>
</div>
<div id="bas_comment"></div>

