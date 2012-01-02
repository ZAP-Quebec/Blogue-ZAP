<?php
/*
Template Name: Accueil
*/
?>

<?php get_header(); 

?>

			<h2 class="title" id="aider">Nous aidons les endroits publics, les commerces et les entreprises de Québec à offrir du Web sans-fil aux citoyens </h2>
			
			<h2 class="title" id="devenez">Devenez une Zone d’Accès Public dès aujourd’hui en suivant ces trois étapes&nbsp;:</h2>
		
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
					<span>Vos clients peuvent dès lors se connecter à internet et profiter du confort de votre commerce pour naviguer pleinement.</span>
					<span class="more"><a href="<?php get_bloginfo('url'); ?>/devenez-une-zap">En savoir plus...</a></span>
				</li>
			</ul>
			<h3 id="derniere_nouv"><a href="<?php bloginfo('rss2_url'); ?>" ></a></h3>
                        <div id="app_mobile"><a href="http://itunes.apple.com/ca/app/zap-quebec-wifi-gratuit-partout/id381381665?mt=8"></a></div>
			<div id="news_container" class="graybox accueil">
				<div id="haut"></div>
				<div id="bas"></div>
				
			<?php 

			$i = 0;
			$query = new WP_Query('showposts=13');

			while ($query->have_posts()) : $query->the_post();
				if( $i<= 2){?>
					<? if($i ==0){ ?>
					<div id="text_content">
					<? } ?>
						<h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
						<p><?php the_excerpt(); ?></p>
						<a href="<?php the_permalink(); ?>">Lire la suite</a>
						<?php zapqc_get_description_line() ?>
					<? if($i ==2){ ?>
						<a href="<?php get_bloginfo('home'); ?>nouvelles"><img src="<?php bloginfo('template_url'); ?>/images/btn_voirlesautresnouvelles.png" alt="Plus de nouvelles" /></a>
					</div>
					<div id="sidebar"><ul id="news_feed">
						<li class="title"> ÉGALEMENT </li>
					<? } ?>
						
				<?php }else{ ?>
						<li><a href="<?php echo the_permalink(); ?>"><h4 class="news"><?php the_title(); ?></h4></a></li>
				<?php }
				$i++;
			endwhile;
			?>
				</ul>
				<div id="coordonnes"><span class="title">NOS COORDONNÉES</span>
<p>ZAP Québec<br />CP 20005, CSP Belvédère<br />Québec, QC G1S 4Z2</p><p>+1 (418) 263-8020<br /><a href="mailto:info@zapquebec.org">info@zapquebec.org</a></p></div></div>
				
			</div>
		
		
		
<?php get_footer(); ?>


