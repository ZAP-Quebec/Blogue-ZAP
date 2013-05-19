<?php
/*
Template Name: Accueil
*/
?>

<?php get_header(); 

?>

			<h2 class="title" id="aider">Nous aidons les endroits <em>publics</em>, les <em>commerces</em> et les <em>entreprises</em> de Québec à offrir du <strong>Web sans-fil</strong> aux citoyens </h2>
			
			<h3 id="derniere_nouv"><a href="<?php bloginfo('rss2_url'); ?>" ></a></h3>

			<section class="nouvelles">
				
			<?php 

			$i = 0;
			$query = new WP_Query('showposts=8');

			while ($query->have_posts()) : $query->the_post();
				if( $i<= 2){?>
					<? if($i ==0){ ?>
					<div class="liste">
					<? } ?>
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<?php zapqc_get_description_line() ?>
						<p><?php the_excerpt(); ?></p>
						<div class="suite">
							<a href="<?php the_permalink(); ?>">Lire la suite</a> |  
							<a href="<?php comments_link('Commenter l’article', '1 commentaire', '% commentaires'); ?>" class="comment">Commenter l'article</a>
						</div>
					<? if($i ==2){ ?>
						<a href="<?php get_bloginfo('home'); ?>nouvelles" class="liste">Toutes les nouvelles</a>
					</div>
					<aside class="sidebar">
						<h3>Également</h3>
						<ul>
					<? } ?>
						
				<?php }else{ ?>
						<li><a href="<?php echo the_permalink(); ?>"><?php the_title(); ?></a></li>
				<?php }
				$i++;
			endwhile;
			?>
				</ul>
			</aside>
			</section>

			<h2 class="title devenez">Devenez une Zone d’Accès Public dès aujourd’hui en suivant ces trois étapes&nbsp;:</h2>
			<div class="howTo_zap">
				<ul>
					<li class="etape_rp">
						<span class="icone"></span><h3 class="sub-title">Rencontre préliminaire</h3>
						<div>
							Un représentant ZAP Québec se rend sur place afin de conclure l’entente de principe et répondre à vos questions par rapport à nos services.
						</div>
					</li>
					<li class="etape_ins">
						<span class="icone"></span><h3 class="sub-title">Installation</h3>
						<div>
							Un technicien ZAP Québec se rend sur place pour configurer l’équipement nécessaire afin de partager votre connexion haute vitesse avec vos clients.
						</div>

					</li>
					<li class="etape_vez">
						<span class="icone"></span><h3 class="sub-title">Vous êtes une ZAP</h3>
						<div>
							Vos clients peuvent dès lors se connecter à internet et profiter du confort de votre commerce pour naviguer pleinement.
							<span class="more"><a href="<?php get_bloginfo('url'); ?>/devenez-une-zap">En savoir plus...</a></span>
						</div>
					</li>
				</ul>
			</div>
				
		
		
		
<?php get_footer(); ?>


