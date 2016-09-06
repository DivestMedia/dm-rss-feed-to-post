<?php if(!defined('DM_RSS_VERSION')) die('Fatal Error');

/*
* Divest Media RSS Feed to Post Object Class File
*/
if(!class_exists('RSSMink')){

    class RSSMink{

        public $rss;
        public $count = 0;
        public $log = [];
        public $feed = null;
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
        public $ignores = [];

        function __CONSTRUCT($id = null){
            if($id){
                // Loading Feed Data
                $this->feed = get_posts($id);
                // Loading Feed Meta Data
                foreach ($this->meta as $key => $value) {
                    $this->meta[$key] = get_post_meta($id,$key,true);
                }

                // Validate RSS
                $this->rss = $this->meta['_rss_link'];
                $valid = $this->checkIfValidRSS($this->rss);
                if($valid !== false){
                    $this->count =  $valid ?: 0;

                }else{
                    return false;
                }
            }else{
                return false;
            }
        }
        public function visit($url){
            $driver = new \Behat\Mink\Driver\GoutteDriver();
            $this->browser = new \Behat\Mink\Session($driver);
            $this->browser->start();
            $this->browser->visit($url);
            return $this->browser->getPage();
        }

        function strip_tags_content($text, $tags = '', $invert = FALSE) {

            preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
            $tags = array_unique($tags[1]);

            if(is_array($tags) AND count($tags) > 0) {
                if($invert == FALSE) {
                    return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
                }
                else {
                    return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
                }
            }
            elseif($invert == FALSE) {
                return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
            }
            return $text;
        }

        public function getElemValue($elem,$type){
            switch ($type) {
                case 'text':
                return $elem->getText();
                break;
                case 'html':
                $html = $elem->getHtml();
                $html = $this->strip_tags_content($html,'<script>',true);
                $html = $this->strip_tags_content($html,'<iframe>',true);
                if(!empty($this->ignores)){
                    // $doc = new DOMDocument();
                    // $doc->loadHTML($html);
                    // $selector = new DOMXPath($doc);
                    // foreach ($this->ignores as $value) {
                    //
                    //
                    //     $elements = [];
                    //     switch (substr($value,0,1)) {
                    //         case '#':
                    //         $elements = $selector->query("//*[@id='" . substr($value,1) . "']");
                    //         break;
                    //         case '.':
                    //         $elements = $selector->query("//*[@class='" . substr($value,1) . "']");
                    //         break;
                    //         default:
                    //         $elements = $selector->query("//*" . substr($value,1) . "");
                    //         break;
                    //     }
                    //     foreach($elements as $e ) {
                    //         $e->parentNode->removeChild($e);
                    //     }
                    // }
                    // $html = $doc->saveHTML($doc->documentElement);
                    $htmlremove = [];
                    foreach ($this->ignores as $ignore) {
                        $ignoreelem = $elem->find('css',$ignore);
                        if($ignoreelem){
                            $htmlremove[] = $ignoreelem->getHtml();
                        }
                    }
                    $html = str_replace($htmlremove, "", $html);
                }
                return strip_tags($html,'<br><div><p><strong><ul><li><ol>');
                break;
                case 'inputvalue':
                return $elem->getValue();
                break;
                case 'attrsrc':
                case 'attrcontent':
                case 'attrhref':
                case 'attrname':
                $type = str_replace('attr','',$type);
                return $elem->getAttribute($type);
                break;
                case 'asis':
                return $elem;
                break;
                default:
                return $elem->getOuterHtml();
                break;
            }
        }

        public function getRssItems($offset = 0,$limit = 10){

            $this->logger('Test','Reading RSS Feed',2);

            $feedrss = new SimplePie();
            $feedrss->set_feed_url($this->rss);
            $feedrss->set_cache_location( ABSPATH . 'wp-content/cache');
            $feedrss->init();
            $feedrss->handle_content_type();
            $links = [];
            if(count($feedrss->get_items($offset,$limit))<1){
                $this->logger('Test','Reading RSS Feed : No Items fetched',0);
            }
            foreach ($feedrss->get_items($offset,$limit) as $k => $item) {

                $links[$k] = [
                    [
                        'label' => 'Post Title',
                        'key' => 'post-title',
                        'value' => esc_html($item->get_title())
                    ],
                    [
                        'label' => 'Post URL',
                        'key' => 'post-url',
                        'value' => esc_html($item->get_permalink())
                    ],
                    [
                        'label' => 'Post Excerpt',
                        'key' => 'post-excerpt',
                        'value' => esc_html($item->get_description())
                    ],
                ];


                // Visit the Page
                $this->logger('Grab','Grabbing Feed item #'.($k+1),2);
                $feed = $this->visit($item->get_permalink());

                if(!empty($this->meta['_rss_post_ignore'])){
                    $xpathignore = preg_split('/\r\n|[\r\n]/', $this->meta['_rss_post_ignore']);
                    foreach ($xpathignore as $value) {
                        $this->ignores[] = $value;
                    }
                }


                if($feed){
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

                                // Lookup Type
                                foreach ($d['type'] as $kk => $vv) {
                                    $this->logger('Grab','Feed item #'.($k+1).'. Looking for '.(!in_array($field,[
                                        '_rss_post_meta',
                                        '_rss_post_tags',
                                        ]) ? $field : $d['meta'][$kk]),2);
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
                                            $links[$k][] = [
                                                'label' => $label,
                                                'key' => $this->slug($label),
                                                'value' => $this->getElemValue($elem,$d['selector'][$kk])
                                            ];
                                            $this->logger('Grab','Feed item #'.($k+1).'. '.(!in_array($field,[
                                                '_rss_post_meta',
                                                '_rss_post_tags',
                                                ]) ? $field : $d['meta'][$kk]) . ' found',1);
                                            }
                                            else {
                                                $this->logger('Grab','Feed item #'.($k+1).'. '.(!in_array($field,[
                                                    '_rss_post_meta',
                                                    '_rss_post_tags',
                                                    ]) ? $field : $d['meta'][$kk]) . ' is empty',3);
                                                    $links[$k][] = [
                                                        'label' => $label,
                                                        'key' => $this->slug($label),
                                                        'value' => '-'
                                                    ];
                                                }
                                            }
                                        }
                                    }else{
                                        $this->logger('Grab','Feed item #'.($k+1).' is empty',0);
                                    }
                                }


                                return $links;
                            }

                            function logger($type,$message,$level){
                                $lognow = [
                                    'status' => ['ERROR','OK','INFO','WARNING'][$level], // 0 = Error, 1 = OK , 2 = Info , 3 = Warning
                                    'type' => $type,
                                    'message' => $message,
                                    'time' => date('Y-m-d H:i:s')
                                ];
                                $this->log[] = $lognow;

                                $logfile = fopen(DM_RSS_PLUGIN_DIR . "dmrss.log", "a") or die("Unable to open file!");
                                $txt = JSON_ENCODE($lognow)."\n";
                                fwrite($logfile, $txt);
                                fclose($logfile);
                            }

                            function file_get_contents_curl($url) {
                                $ch = curl_init();

                                curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
                                curl_setopt($ch, CURLOPT_HEADER, 0);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                curl_setopt($ch, CURLOPT_URL, $url);
                                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

                                $data = curl_exec($ch);
                                curl_close($ch);

                                return $data;
                            }

                            function checkIfValidRSS($url){
                                $this->logger('Test','Checking RSS URL',2);
                                if($this->checkUrl($url)){
                                    try {

                                        $rss = new SimpleXmlElement($this->file_get_contents_curl($url));

                                        // $rss = new SimpleXmlElement(file_get_contents($url,false,stream_context_create([
                                        //     "ssl"=> [
                                        //         "verify_peer"=>false,
                                        //         "verify_peer_name"=>false,
                                        //     ],
                                        // ])));

                                        if(isset($rss->channel->item)){
                                            $items = (int)$rss->channel->item->count();
                                            $this->logger('Test','RSS has '.$items.' items',2);
                                            return $items;
                                        }else{
                                            $this->logger('Test','RSS has no items',3);
                                            return 0;
                                        }
                                    }
                                    catch(Exception $e){
                                        $this->logger('Test','RSS is invalid . Error : '.$e->getMessage(),0);
                                        return false;
                                    }
                                }

                                $this->logger('Test','RSS URL is unreachable',0);
                                return false;
                            }

                            function checkUrl($url=NULL){
                                $this->logger('Test','Pinging URL Address',2);
                                if($url == NULL){
                                    $this->logger('Test','No URL given',0);
                                    return false;
                                }
                                $ch = curl_init($url);
                                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                $data = curl_exec($ch);
                                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                curl_close($ch);
                                if($httpcode>=200 && $httpcode<300){
                                    $this->logger('Test','URL reachable',1);
                                    return true;
                                } else {
                                    $this->logger('Test','URL unreachable',0);
                                    return false;
                                }
                            }

                            public static function slug($title, $separator = '-')
                            {
                                // $title = static::ascii($title);
                                // Convert all dashes/underscores into separator
                                $flip = $separator == '-' ? '_' : '-';
                                $title = preg_replace('!['.preg_quote($flip).']+!u', $separator, $title);
                                // Remove all characters that are not the separator, letters, numbers, or whitespace.
                                $title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($title));
                                // Replace all separator characters and whitespace by a single separator
                                $title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);
                                return trim($title, $separator);
                            }
                        }
                    }
