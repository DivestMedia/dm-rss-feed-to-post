<?php
get_header();
?>
<section>
	<div class="container">
		<!-- <a href="#" class="text-gray bold size-10 uppercase letter-spacing-10"><?php //echo !empty(get_the_category($post->ID)[0]->name)?get_the_category($post->ID)[0]->name:'';?></a> -->
		<header class="text-left margin-bottom-50 tiny-line">
			<h2 class="font-proxima"><a href=""><?=$_news['post-title']?></a></h2>
			<a href="#" class="size-12 text-gray"><?=$_news['published-date']?></a>
			<br/>
		</header>

		<div class="row">
			<div class="col-md-2 col-sm-3"></div>
			<div class="col-md-6 col-sm-9 text-justify ">
				<figure>
					<img width="450" height="300" src="<?=$_news['post-thumbnail']?>" class="img-responsive margin-bottom-30 wp-post-image" alt="<?=$_news['post-title']?>">
				</figure>
				<div class="post-content">
					<?=$_news['post-content']?>
				</div>
				<div class="divider divider-dotted"><!-- divider --></div>
				<p class="text-left">
					Read original article on <a href="<?=$_news['post-url']?>" target="_blank"><?=$_news['post-url']?></a>
				</p>
				
			</div>
			<div class="col-lg-4 col-md-4 text-left hidden-xs hidden-sm">
				<!-- CATEGORIES -->
				<div class="side-nav margin-bottom-10 ">

						<?php
							render_side_bar_widget();
							?>

						</div>
						<!-- /CATEGORIES -->


					</div>
					<div class="col-sm-6 col-md-3 hidden-xs hidden-sm">
						<?php
						if(is_active_sidebar('sidebar-ads')){
							dynamic_sidebar('sidebar-ads');
						}
						?>
					</div>
				</div>
			</div>
		</section>
<?php
	get_footer();
?>