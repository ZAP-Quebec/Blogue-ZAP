<?php $cur_lang = zapqc_get_cur_lang(); ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
	<meta charset="<?php bloginfo('charset'); ?>">


	<title><?php bloginfo('name'); ?> <?php if ( is_single() ) { ?> &raquo; Blog Archive <?php } ?> <?php wp_title(); ?></title>


	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />


	<?php wp_head(); ?>
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />

	</head>
	<body >
		<div id="conteneur">
			<ul id="menu">
				<li id="menu_oslz"> <a href="http://www.moijezap.org/ou"> </a></li>
				<li id="menu_duz" <?php if(is_page("devenez-une-zap")){echo 'class="duz_active"';} ?>> <a href="<?php get_bloginfo('home'); ?>devenez-une-zap"> </a></li>
				<li id="menu_nouv" <?php if(!is_front_page() && !is_page()){echo 'class="nouv_active"';} ?>><a href="<?php get_bloginfo('home'); ?>nouvelles"> </a></li>
				<li id="menu_tsz" <?php if(is_page() && !is_front_page() && !is_page("devenez-une-zap") && !is_page("zap-nomade")){echo 'class="tsz_active"';} ?>> <a href="<?php bloginfo('url'); ?>/notreprojet"> </a></li>
			</ul>
			<div id="content">
				
				<ul id="langues">
					<li class="connect"><a href="http://moijezap.org">Procédure de connexion</a></li>
					<li  <?php if($cur_lang == 'FR'){echo 'class="active"';} ?>><a href="<?php bloginfo('url'); ?>/">FR</a></li>
					<li  <?php if($cur_lang == 'EN'){echo 'class="active"';} ?>><a href="<?php bloginfo('url'); ?>/english">EN</a></li>
					<li  <?php if($cur_lang == 'ES'){echo 'class="active"';} ?>><a href="<?php bloginfo('url'); ?>/espanol">ES</a></li>
				</ul>

				<div id="bandeau">
					<div id="logo"><a href="<?php bloginfo('url'); ?>/"><img src="<?php bloginfo('template_directory'); ?>/images/logo_zapmini.png" /></a></div>
					<div id="zsd">
					    <div id="promo" class="<?php echo $cur_lang ?>">
					        <a href="http://www.moijezap.org/<?php echo strtolower($cur_lang); ?>_apropos.php#comment" id="btn_goto_lang" class="<?php echo zapqc_get_cur_lang(); ?>"></a>
					        <a href="<?php bloginfo('url'); ?>/zap-nomade/" id="lien_zap_nomade"></a>    
					    </div>
					
					</div>
				</div>
				
				<?php if(!is_front_page()){ zapqc_get_compact_duz();} ?>
	                         
