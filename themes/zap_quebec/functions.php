<?php
if ( function_exists('register_sidebar') )
    register_sidebar(array(
        'before_widget' => '<li id="%1$s" class="widget %2$s">',
        'after_widget' => '</li>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>',
    ));

function zapqc_get_prev_suiv(){
?>
		<div class="navigation">
			<div class="alignleft"><?php next_posts_link('&nbsp;') ?></div>
			<div class="alignright"><?php previous_posts_link('&nbsp;') ?></div>
		</div>
<?php
}

function zapqc_get_compact_duz()
{
?>
<ul id="tease_duz"><li id="duz_img"><a href="<?php get_bloginfo('url'); ?>devenez-une-zap"></a></li><li id="duz_text"><a href="<?php get_bloginfo('home'); ?>devenez-une-zap"></a></li></ul>
<?php
}

function zapqc_get_description_line()
{
?>
<div class="postmetadata"><p class="post_info"><?php /*the_tags('Tags: ', ', ', '<br />'); ?> Publier dans <?php the_category(', ') */?> par <? the_author()?> le <?php the_time('j F Y') ?></p> 
	 <p class="post_social"><a href="<?php comments_link('Commenter l’article', '1 commentaire', '% commentaires'); ?>">Commenter l'article</a> • Partager sur <a href="http://www.facebook.com/sharer.php?u=<?php the_permalink(); ?>" class="share_fb">Facebook</a> <a href="http://del.icio.us/post?url=<?php the_permalink(); ?>" class="share_deli">Delicious</a></p></div>
<?php
}

// Function: Snippet Text
function snippet_text($text, $length = 0) {
	$words = preg_split('/\s+/', ltrim($text), $length + 1);
	if(count($words) > $length) {
		return rtrim(substr($text, 0, strlen($text) - strlen(end($words)))).' ...';
	} else {
		return $text;
	}
}

function zapqc_get_arbitrary_page_list(){
	return '9,226,276,272,284,269,287,229';
}

function zapqc_get_excluded_pages()
{
	return  '4,6,251,246';
}
// Retourne les pages pour la section tout sur zap
function zapqc_get_page_list()
{
/*	$pages = zapqc_get_arbitrary_page_list();
	$p_array = split(',',$pages);

	for($i = 0; $i < count($p_array); $i++)
	{
		wp_list_pages(array('title_li'=>'', 'include'=>$p_array[$i]));
	}
	wp_list_pages(array('title_li'=>'','exclude'=>zapqc_get_arbitrary_page_list().','.zapqc_get_excluded_pages() ));*/
    wp_list_pages(array('title_li'=> '', 'meta_value'=>'tout sur zap', 'meta_key'=>'section'));
}

function zapqc_get_cur_lang()
{

    // Make the post variable global so I can use it in my function
    global $post;
    $val =  get_post_custom_values("langage", $post->ID);
    // Langage is only supossed to have 1 value, so we always take index 0
    $val = $val[0];
    // Empty value mean default language (in this case FR) 
    if($val == '')
    {
        // This should not me hardcoded
        $val = 'FR';
    }

    return $val;
}

?>
