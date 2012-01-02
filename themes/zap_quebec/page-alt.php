<?php
/*
 Template Name: Page ALT
*/ 
?>

<?php get_header(); ?>
		
			<ul id="howTo_zap">
				<li id="etape_rp">
					<div class="icone"></div><h2 class="sub-title">Rencontre préliminaire</h2>
					<span>Un représentant ZAP Québec se rend sur place afin de conclure l’entente de principe et répondre à vos questions par rapport à nos services.</span>
				</li>
				<li id="etape_ins">
					<div class="icone"></div><h2 class="sub-title">Installation</h2>
					<span>Un technicien ZAP Québec se rend sur place pour configurer l’équipement nécessaire afin de partager votre connexion haute vitesse avec vos clients.</span>

				</li>
				<li id="etape_vez">
					<div class="icone"></div><h2 class="sub-title">Vous êtes une ZAP</h2>
					<span>Vos clients peuvent dès lors se connecter à internet et profiter du comfort de votre commerce pour naviguez pleinement.</span>
				</li>
			</ul>
		<h3 id="title_tsz"></h3>
		<div id="tout_sur_zap" class="graybox">
			<div id="haut"></div>
			<div id="bas"></div>
			
			<div id="text_content">
			<?php if (have_posts()) : ?>
			<?php while (have_posts()) : the_post(); ?>
			<h1><?php the_title(); ?></h1>
				<?php the_content(); ?>
				
			<?php endwhile; ?>
			<?php else : ?>

					<h2 class="center">Aucun résultat</h2>
					<p class="center">Vous avez demandé quelque chose que nous n'avons pas. Désolé</p>
		

				<?php endif; ?>
			</div>
			<div id="sidebar">
			
			<ul id="liste_pages">
				<li id="recherche">
					<? include("searchform.php"); ?>
				</li>
				<?php wp_list_pages(array('title_li'=>'','include'=>zapqc_get_arbitrary_page_list() )); ?>
			</div>
			<div class="clearer"></div>
		</div>
		
		
		
<?php get_footer(); ?>
