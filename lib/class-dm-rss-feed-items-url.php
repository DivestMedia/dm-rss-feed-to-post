<?php if(!defined('DM_RSS_VERSION')) die('Fatal Error');

/*
* Divest Media RSS Feed Items URL Object Class File
*/

if(!class_exists('RSSFIURL')){
	class RSSFIRUL extends RSSMink{
		public $browser = null;
		public $meta = [
            '_rss_link' => null ,
            '_rss_post_type' => null ,
            '_rss_post_thumbnail' => null ,
            '_rss_post_content' => null ,
            '_rss_post_author' => null ,
            '_rss_post_meta' => null ,
            '_rss_post_tags' => null ,
            '_rss_post_category' => null ,
            '_rss_post_published' => null ,
            '_rss_post_ignore' => null ,
        ];
		public function __construct(){
			// self::getFeedItemsByUrl('UFC NEWS');
			self::custom_template_init();
		}
		public function rssfiurl_create_table(){
			global $wpdb;
			$table_name = $wpdb->prefix . 'feed_items_urls';
			if($wpdb->get_var("show tables like '$table_name'") != $table_name){
				$sql = "CREATE TABLE IF NOT EXISTS ".$table_name." (
				  `id` bigint(20) NOT NULL AUTO_INCREMENT,
				  `title` TEXT NOT NULL,
				  `name` varchar(200) NOT NULL,
				  `description` TEXT NOT NULL,
				  `url` varchar(255) NOT NULL,
				  `rss_id` bigint(20) NOT NULL,
				  `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  UNIQUE KEY id (id)
				);";
				$wpdb->query($sql);
			}
		}
	  	
		public function saveObjectToFile($_filename,$_data){
            if(!file_exists($_filename)){
                $_news = $_data;
                
                $dist = self::resize_image($_news['post-thumbnail'],450,300);
                ob_start();
                imagejpeg($dist, null, 75);
                $img = ob_get_clean();
                imagedestroy($dist);
                $type = pathinfo($_news['post-thumbnail'], PATHINFO_EXTENSION);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($img);
                $_news['post-thumbnail'] = $base64;
                $_newfilename = fopen($_filename, "w") or die("Unable to open file!");
                fwrite($_newfilename, json_encode($_news));
                fclose($_newfilename);
            }
		}

		public function getFeedItemsByUrl($_rss_post_name = 'UFC Latest News',$_limit = 10, $_page = 1,$_feed_items_url=false){
			$_rss = get_page_by_title($_rss_post_name,'OBJECT','rss_feed');
			$_offset = ($_page * $_limit) - $_limit; 
			$links = [];

			if($_rss_post_name=='all news'){
				global $wpdb;
				$table_name = $wpdb->prefix . 'feed_items_urls';
				if(empty($_feed_items_url))
					$_feed_items_url = $wpdb->get_results(' SELECT `id`,`url`,`title`,`name`,`rss_id` FROM '.$table_name.' ORDER BY `date_created` DESC LIMIT '.$_offset.','.$_limit);
	        }elseif(!empty($_rss->ID)){
				$_rss_id = $_rss->ID; 

				
				global $wpdb;
				$table_name = $wpdb->prefix . 'feed_items_urls';
				if(empty($_feed_items_url))
					$_feed_items_url = $wpdb->get_results(' SELECT `id`,`url`,`title`,`name`,`rss_id` FROM '.$table_name.' WHERE `rss_id` = "'.$_rss_id.'" ORDER BY `date_created` DESC LIMIT '.$_offset.','.$_limit);
	        }
			
	        	if($_feed_items_url){

		        		
			            foreach ($_feed_items_url as $k => $_item) {
	        				$_rss_id = $_item->rss_id;
	        				
			            	foreach ($this->meta as $key => $value) {
				                $this->meta[$key] = get_post_meta($_rss_id,$key,true);
				            }

			            	$_filename = DM_RSS_PLUGIN_DIR.'news-cache/'.md5($_item->id.$_item->name).'.json';
		                    if(file_exists($_filename)){
		                    	$links[$k] = (array)json_decode(file_get_contents($_filename));
		                    	// return $links;
		                    }else{
		                    	$_item->url = str_replace('https', 'http', $_item->url);
				            	$feed = $this->visit($_item->url);
				            	$links[$k]['post-id'] = $_item->id;
				            	$links[$k]['post-title'] = $_item->title;
				            	$links[$k]['post-name'] = trim(self::seoUrl($_item->title),'-');
				            	$links[$k]['post-url'] = $_item->url;
				            	if(!empty($feed)){
					                foreach ([
					                    '_rss_post_thumbnail' => 'Post Thumbnail',
					                    '_rss_post_published' => 'Published Date',
					                    '_rss_post_content' => 'Post Content',
					                    '_rss_post_author' => 'Post Author',
					                    '_rss_post_meta' => 'Custom Meta',
					                    '_rss_post_tags' => 'Post Tags'
					                    ] as $field => $label) {

					                    $d = $this->meta[$field];
					                    if(!in_array($field,[
					                        '_rss_post_meta',
					                        '_rss_post_tags',
					                    ])){
					                        $dd = [];
					                        foreach ($d as $kkk => $vvv) {
					                            $dd[$kkk][] = $vvv;
					                        }

					                        $d = $dd;
					                    }
					                    
					                    if(!empty($d['type']))
					                    // Lookup Type
					                    foreach ($d['type'] as $kk => $vv) {
					                        switch ($vv) {
					                            case 'Full-Content':
					                            case 'Title Only':
					                            case 'Content Only':
					                            $found = 0;
					                            $feedgrabtitle = '';
					                            $feedgrabcontent = '';
					                            $feedgrabbody = '';

					                            foreach ($links[$k] as $linkdata) {
					                                if($linkdata['key']=='post-title'){
					                                    $feedgrabtitle = strip_tags($linkdata['value']);
					                                }
					                                if($linkdata['key']=='post-content'){
					                                    $feedgrabcontent = strip_tags($linkdata['value']);
					                                }
					                            }

					                            if($vv=='Full-Content'){
					                                $feedgrabbody = $feedgrabtitle . $feedgrabcontent;
					                            }elseif($vv=='Title Only'){
					                                $feedgrabbody = $feedgrabtitle;
					                            }elseif($vv=='Content Only'){
					                                $feedgrabbody = $feedgrabcontent;
					                            }

					                            $kw = explode(',',$d['query'][$kk]);

					                            foreach ($kw as $kwk => $kwv) {
					                                $kwv = trim($kwv);
					                                if(!empty($kwv) && stripos($feedgrabbody,$kwv)!==FALSE){
					                                    $found++;
					                                }
					                            }

					                            $elem = [
					                                'found' => $found,
					                                'keywords' => $kw,
					                                'type' => $vv,
					                                'validate' => $d['selector'][$kk]
					                            ];

					                            $d['selector'][$kk] = 'asis';
					                            break;
					                            case 'XPATH':
					                            $elem = $feed->find('xpath', $d['query'][$kk]);
					                            break;
					                            case 'CSS':
					                            $elem = $feed->find('css', $d['query'][$kk]);
					                            break;
					                            case 'ID':
					                            $elem = $feed->findById(trim($d['query'][$kk],'#'));
					                            break;
					                            case 'NAME':
					                            default:
					                            $elem = $feed->find('named', array('id_or_name', $this->browser->getSelectorsHandler()->xpathLiteral($d['query'][$kk])));
					                            break;
					                        }

					                        if(in_array($field,['_rss_post_meta',])){
					                            $label = 'Meta: ' . $d['meta'][$kk];
					                        }

					                        if(in_array($field,['_rss_post_tags',])){
					                            $label = 'Tags: ' . $d['meta'][$kk];
					                        }
					                        if($elem !== NULL){
					                            $links[$k][$this->slug($label)] = $this->getElemValue($elem,$d['selector'][$kk]);
					                        }
					                        
					                    }
					                }
					            }

					            if(!empty($links[$k])){
					            	$_post_title = '';
					            	if(empty($_rss->post_title)){
					            		
					            		$_post_title = get_the_title($_rss_id);
					            	}else{
					            		$_post_title = $_rss->post_title;
					            	}

					            	if($_post_title=='PXC Latest News'){
					            		if(!empty($links[$k]['post-thumbnail'])){
					            			$links[$k]['post-thumbnail'] = 'http://pacificxtremecombat.com'.$links[$k]['post-thumbnail'];
					            		}
					            	}

				            	 	if(empty($links[$k]['post-thumbnail'])){
					                	$links[$k]['post-thumbnail'] = wp_get_attachment_url(DM_RSS_CG_IMGID);
					                }
				            	 	if(empty($links[$k]['published-date'])){
					                	$links[$k]['published-date'] = '';
					                }
					            	$links[$k]['post-thumbnail'] = str_replace('https', 'http', $links[$k]['post-thumbnail']);
					            	$links[$k]['post-category'] = $_post_title;
						        	self::saveObjectToFile($_filename,$links[$k]);
						        }
						    }
			            }
		            
	            }
            return $links;
   		}


		public function custom_template_init(){
	      add_filter( 'rewrite_rules_array',[$this,'rewriteRules'] );
	      add_filter( 'template_include', [ $this, 'template_include' ],1,1 );
	      add_filter( 'query_vars', [ $this, 'prefix_register_query_var' ] );
	    }

	    public function prefix_register_query_var($vars){
	      $vars[] = 'cpid';
	      $vars[] = 'pname';
	      $vars[] = 'nind';
	      $vars[] = 'ncat';
	      $vars[] = 'ncpage';
	      return $vars;
	    }

	    public function rewriteRules($rules){
	  		$newrules = self::rewrite();
	  		return $newrules + $rules;
	  	}

	  	public function rewrite(){
	  		$newrules = array();
	  		$newrules['news/(.*)/(.*)'] = 'index.php?cpid=$matches[1]&pname=$matches[2]';
	  		$newrules['news/(.*)'] = 'index.php?nind=notindex&cpid=$matches[1]';
	  		$newrules['\b(news)\b'] = 'index.php?nind=notindex';
	  		$newrules['category/news/(.*)/(.*)'] = 'index.php?ncat=$matches[1]&ncpage=$matches[2]';
	  		$newrules['category/news/(.*)'] = 'index.php?ncat=$matches[1]&ncpage=1';

	  		return $newrules;
	  	}

	  	public function removeRules($rules){
	  		$newrules = self::rewrite();
	  		foreach ($newrules as $rule => $rewrite) {
	  	        unset($rules[$rule]);
	  	    }
	  		return $rules;
	  	}
	  	public function template_include($template){
	  		$_cpid = get_query_var( 'cpid' );
  			$_pname = get_query_var('pname');
  			$_nind = get_query_var('nind');
  			$_cat = get_query_var('ncat');
  			$_ncpage = get_query_var('ncpage');
  			$newscategory = [
				'all-news'=>[
					'name'=>'ALL News',
					'active'=>'0',
					'link'=>'/news/',
				],
				'onefc'=>[
					'name'=>'ONEFC',
					'active'=>'0',
					'link'=>'/category/news/onefc',
					'rsstitle' => 'ONEFC Latest News'
				],
				'urcc'=>[
					'name'=>'URCC',
					'active'=>'0',
					'link'=>'/category/news/urcc',
					'rsstitle' => 'URCC Latest News'
				],
				'pxc'=>[
					'name'=>'PXC',
					'active'=>'0',
					'link'=>'/category/news/pxc',
					'rsstitle' => 'PXC Latest News'
				],
				'ufc'=>[
					'name'=>'UFC',
					'active'=>'0',
					'link'=>'/category/news/ufc',
					'rsstitle' => 'UFC Latest News'
				],
			];
  			if(!empty($_cpid)&&!empty($_pname)){
  				if (!file_exists(DM_RSS_PLUGIN_DIR.'news-cache')) {
				    mkdir(DM_RSS_PLUGIN_DIR.'news-cache', 0777, true);
				}
				$_filename = DM_RSS_PLUGIN_DIR.'news-cache/'.md5($_cpid.$_pname).'.json';
				if(!file_exists($_filename)){
					global $wpdb;
	  				$table_name = $wpdb->prefix . 'feed_items_urls';
	  				$res = $wpdb->get_row( 'SELECT * FROM '.$table_name.' WHERE `id` = "'.sanitize_text_field($_cpid).'" AND `name` = "'. sanitize_text_field($_pname) .'" LIMIT 1' );
	  				if(empty($res)){
						global $wp_query;
						$wp_query->set_404();
						status_header( 404 );
						get_template_part( 404 ); 
	  				}else{
	  					$feedtitle = get_the_title($res->rss_id);
	  					if(!empty($feedtitle)){
		  					$res = self::getFeedItemsByUrl($feedtitle,1,1,[(object)['id'=>$res->id,'url'=>$res->url,'title'=>$res->title,'name'=>$res->name,'rss_id'=>$res->rss_id]]);
		  					$_all_news = self::getFeedItemsByUrl('all news',9,1);
		  					$_news = $res[0];
							if(file_exists(get_stylesheet_directory().'/partials/news-template.php'))
								include_once(get_stylesheet_directory().'/partials/news-template.php');
							else
								include_once( DM_RSS_PLUGIN_DIR . 'partials/news-template.php');
	  					}else{
	  						global $wp_query;
							$wp_query->set_404();
							status_header( 404 );
							get_template_part( 404 ); 
	  					}
		  			}
				}else{
					$_all_news = self::getFeedItemsByUrl('all news',9,1);
					$_news = (array)json_decode(file_get_contents($_filename));
					if(file_exists(get_stylesheet_directory().'/partials/news-template.php'))
						include_once(get_stylesheet_directory().'/partials/news-template.php');
					else
						include_once( DM_RSS_PLUGIN_DIR . 'partials/news-template.php');

  				}
  				die();
  			}elseif(!empty($_nind)&&$_nind==='notindex'){
  				$_page = !empty($_cpid)?$_cpid:'1';
  				$_all_news = self::getFeedItemsByUrl('all news',6,$_page);
  				$GLOBALS['featuredTitle'] = ' UFC News';
  				global $wpdb;
  				$table_name = $wpdb->prefix . 'feed_items_urls';
  				if(empty($GLOBALS['totalpages']))
  					$_items_count = $wpdb->get_var( 'SELECT COUNT(*) FROM '.$table_name );
  				$GLOBALS['totalpages'] = ceil($_items_count/6);
  				$GLOBALS['currentpage'] = $_page;

  				$newscategory['all-news']['active'] = 1;
  				$GLOBALS['newscategory'] = $newscategory;
				$GLOBALS['paginationbase'] = '/news/%_%';
				if(file_exists(get_stylesheet_directory().'/partials/all-news-template.php'))
  					include_once(get_stylesheet_directory().'/partials/all-news-template.php');
  				else
  					include_once( DM_RSS_PLUGIN_DIR . 'partials/all-news-template.php');
				die();
  			}elseif(!empty($_cat)&&!empty($_ncpage)){
  				$_page = !empty($_ncpage)?$_ncpage:'1';
  				$_ccat = $newscategory[$_cat];
  				if(!empty($_ccat)){
  					$newscategory[$_cat]['active'] = 1;
  					$_all_news = self::getFeedItemsByUrl($_ccat['rsstitle'],6,$_page);
  					$rssid = get_page_by_title($_ccat['rsstitle'],null,'rss_feed')->ID;
  					global $wpdb;
  					$table_name = $wpdb->prefix . 'feed_items_urls';
  					if(empty($GLOBALS['totalpages']))
	  					$_items_count = $wpdb->get_var( 'SELECT COUNT(*) FROM '.$table_name.' WHERE `rss_id` = "'.$rssid.'" ' );
	  				$GLOBALS['totalpages'] = ceil($_items_count/6);
  					$GLOBALS['currentpage'] = $_page;
  					$GLOBALS['featuredTitle'] = ' '.$_ccat['name'].' News';
  					$GLOBALS['newscategory'] = $newscategory;
  					$GLOBALS['paginationbase'] = '/category/news/'.$_cat.'/%_%';
  				}
  				if(file_exists(get_stylesheet_directory().'/partials/all-news-template.php'))
  					include_once(get_stylesheet_directory().'/partials/all-news-template.php');
  				else
  					include_once( DM_RSS_PLUGIN_DIR . 'partials/all-news-template.php');
  				die();
  			}
  			return $template;
	  	}

	  	public function resize_image($file, $w, $h, $crop=FALSE) {
		    list($width, $height) = getimagesize($file);
		    $r = $width / $height;
		    if ($crop) {
		        if ($width > $height) {
		            $width = ceil($width-($width*abs($r-$w/$h)));
		        } else {
		            $height = ceil($height-($height*abs($r-$w/$h)));
		        }
		        $newwidth = $w;
		        $newheight = $h;
		    } else {
		        if ($w/$h > $r) {
		            $newwidth = $h*$r;
		            $newheight = $h;
		        } else {
		            $newheight = $w/$r;
		            $newwidth = $w;
		        }
		    }
		    $src = imagecreatefromjpeg($file);
		    $dst = imagecreatetruecolor($newwidth, $newheight);
		    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

		    return $dst;
		}

	  	public function seoUrl($string) {
		    //Lower case everything
		    $string = strtolower($string);
		    //Make alphanumeric (removes all other characters)
		    $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
		    //Clean up multiple dashes or whitespaces
		    $string = preg_replace("/[\s-]+/", " ", $string);
		    //Convert whitespaces and underscore to dash
		    $string = preg_replace("/[\s_]/", "-", $string);
		    return $string;
		}
		public function file_get_contents_curl($url){

		    $ch = curl_init();

		    curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (Linux; Android 6.0.1; MotoG3 Build/MPI24.107-55) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.81 Mobile Safari/537.36");
		    // Disable SSL verification
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		    // Will return the response, if false it print the response
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    // Set the url
		    curl_setopt($ch, CURLOPT_URL,$url);
		    // Execute
		    $result=curl_exec($ch);
		    // Closing
		    curl_close($ch);

		    return $result;
		}
	}
}