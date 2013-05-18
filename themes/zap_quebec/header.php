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

		<link href='http://fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700|Noto+Sans' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />

		<?php wp_head() ?>
		<?php // Loads HTML5 JavaScript file to add support for HTML5 elements in older IE versions. ?>
		<!--[if lt IE 9]>
		<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
		<![endif]-->

		<script src="<?php bloginfo('template_url'); ?>/javascripts/vendor/custom.modernizr.js"></script>
	</head>
	<body >
		<header>
			<?php wp_nav_menu( array( 'theme_location' => 'lang', 'menu_class' => 'nav-lang', 'container' =>'nav' ) ); ?>

			<div class="bandeau">
				<div class="logo"><a href="<?php bloginfo('url'); ?>/"><img src="<?php bloginfo('template_directory'); ?>/images/logo_zapmini.png" /></a></div>
			</div>
		
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'menu_class' => 'nav-menu', 'container' =>'nav' ) ); ?>
			
			<?php if(!is_front_page()){ zapqc_get_compact_duz();} ?>
        </header>  
        <div id="main">            
