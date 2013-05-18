<!DOCTYPE html>
<!--[if IE 7]>
<html class="ie ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html class="ie ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
		<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width" />
		<title><?php wp_title( '|', true, 'right' ); ?></title>
		<link rel="profile" href="http://gmpg.org/xfn/11" />
		<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
		<?php // Loads HTML5 JavaScript file to add support for HTML5 elements in older IE versions. ?>
		<!--[if lt IE 9]>
		<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
		<![endif]-->

		<script src="<?php bloginfo('template_url'); ?>/javascripts/vendor/custom.modernizr.js"></script>
	</head>
	<body >
		<div id="conteneur">


			<nav id="menu">
				<?php wp_nav_menu( array( 'theme_location' => 'primary', 'menu_class' => 'nav-menu' ) ); ?>
			</nav>
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
	                         
