<?php $cur_lang = zapqc_get_cur_lang(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />

<title><?php bloginfo('name'); ?> <?php if ( is_single() ) { ?> &raquo; Blog Archive <?php } ?> <?php wp_title(); ?></title>

<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.6.0/build/fonts/fonts-min.css" />
<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.6.0/build/reset/reset-min.css" />


<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />


<?php wp_head(); ?>
<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<!--[if IE 6]>
<link type="text/css" rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/ie6.css" />
<![endif]-->

<!--[if IE 7]>
<link type="text/css" rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/ie7.css" />
<![endif]-->

<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/js/jquery-1.3.min.js"></script>

<script type="text/javascript">
    // Create the urls for each language
    var lang = Array();
    
    lang['FR'] = '<?php bloginfo('url'); ?>/';
    lang['EN'] = '<?php bloginfo('url'); ?>/english';
    lang['ES'] = '<?php bloginfo('url'); ?>/espanol';
    
    function switchLang()
    {
        // Change the currently selected language tab and move the graphic accordingly
        $('#promo').removeClass().addClass(this.id);
        $('#btn_goto_lang').removeClass().addClass(this.id).attr('href', lang[this.id]);
        $(".lang").parent().removeClass('active');
        $(this).parent().addClass('active');
    }  
    function initLangSwitcher()    
    {
        // Remove the targets from the links in the list and bind the click events
        //$('.lang').click(switchLang).attr('href', '#');
    }
    
    $(document).ready(initLangSwitcher);
</script>

</head>
<body>
<body class="yui-skin-sam">
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
					<!--
				    <ul>
				        <li class="<?php if($cur_lang == 'FR'){echo "active";} ?>"><a href="<?php bloginfo('url'); ?>" id="FR" class="lang">FR</a></li>
				        <li class="<?php if($cur_lang == 'EN'){echo "active";} ?>"><a href="<?php bloginfo('url'); ?>/english" class="lang" id="EN">EN</a></li>
				        <li class="<?php if($cur_lang == 'ES'){echo "active";} ?>"><a href="<?php bloginfo('url'); ?>/espanol" class="lang" id="ES">ES</a></li>
				    </ul>
				    -->
				    <div id="promo" class="<?php echo $cur_lang ?>">
				        <a href="http://www.moijezap.org/<?php echo strtolower($cur_lang); ?>_apropos.php#comment" id="btn_goto_lang" class="<?php echo zapqc_get_cur_lang(); ?>"></a>
				        <a href="<?php bloginfo('url'); ?>/zap-nomade/" id="lien_zap_nomade"></a>    
				    </div>
				
				</div>
			</div>
			
			<?php if(!is_front_page()){ zapqc_get_compact_duz();} ?>
                         
