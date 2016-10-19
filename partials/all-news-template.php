<?php
get_header();
global $newscategory,$featuredTitle,$is_article,$totalpages,$currentpage,$paginationbase;
?>

<section class="alternate">
	<div class="container">
		<header class="text-center margin-bottom-10 tiny-line">
			<h2 class="font-proxima uppercase"><?=($featuredTitle)?></h2>
		</header>

		<!-- Tab v3 -->
		<div class="row tab-v3">
			<div class="col-sm-3 hidden-xs hidden-sm">
				<!-- side navigation -->
				<div class="side-nav margin-top-50">
					<?php if(count($newscategory)): ?>
						<div class="side-nav-head">
							<button class="fa fa-bars"></button>
							<h4>CATEGORIES</h4>
						</div>
						<ul class="list-group list-unstyled nav nav-tabs nav-stacked nav-alternate uppercase">
							<?php foreach ($newscategory as $category): ?>
								<li class="list-group-item <?=((!empty($category['active']) && $category['active']==true) ? 'active' : '')?>">
									<a href="<?=($category['link'] ?: '#')?>"><?=($category['name'] ?: 'Uncategorized')?></a>
								</li>
							<?php endforeach;?>
						</ul>
					<?php endif;?>

					<?php render_side_bar_widget();?>
				</div>
				<!-- /side navigation -->
			</div>
			<div class="col-sm-9">
				<div class="tab-content">
					<div class="tab-pane fade in active" id="planning">
						<div class="row" id="news-row">

							<?php
							if(count($_all_news)):
								foreach($_all_news as $_news):
									$post_url = home_url('/news/'.$_news['post-id'].'/'.$_news['post-name']);
							?>
							<div class="col-sm-4">
								<a href="<?=$post_url?>">
									<figure style="border-bottom: 5px solid #1ecd6e;background-image: url('<?=$_news['post-thumbnail']?>');background-size: cover;background-repeat: no-repeat;height: 150px;"></figure>
								</a>
								<h4 class="margin-top-20 size-14 weight-700 uppercase height-50" style="overflow:hidden;"><a href="<?=$post_url?>"><?=xyr_smarty_limit_chars($_news['post-title'],80)?></a></h4>
								<p class="text-justify height-100" style="overflow:hidden;"><?=trim_text($_news['post-content'],180)?></p>
								<ul class="text-left size-12 list-inline list-separator">
									<li>
										<?php if(!empty($_news['published-date'])){?>
										<i class="fa fa-calendar"></i>
										<?=$_news['published-date']?>
										<?php }?>
									</li>
								</ul>
							</div>
							<?php
								endforeach;
							else:
								echo '<h4 class="text-center">No Articles yet</h4>';
							endif;
							?>


		</div>
		<?php 
		$pages = paginate_links(array(
			'base'               => $paginationbase,
			'format'             => '%#%',
			'total'              => $totalpages,
			'current'            => $currentpage,
			'show_all'           => false,
			'prev_next'          => true,
			'prev_text' 		=> '&larr; Prev',
		    'next_text' 		=> 'Next &rarr;',
			'type'               => 'array',
			'add_args'           => false,
		)); 		
		if (is_array($pages)) {
	        echo '<ul class="pagination">';
	        foreach ($pages as $i => $page) {
	            if ($currentpage == 1 && $i == 0) {
	                echo "<li class='active'>$page</li>";
	            } else {
	                if ($currentpage != 1 && $currentpage == $i) {
	                    echo "<li class='active'>$page</li>";
	                } else {
	                    echo "<li>$page</li>";
	                }
	            }
	        }
	        echo '</ul>';
	    }
?>
		
	</div>
</div>
</div>
</div>
<!-- Tab v3 -->
</div>
</section>
<?php
get_footer();
?>