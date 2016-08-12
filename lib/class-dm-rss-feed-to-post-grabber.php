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
        public $meta = [
            '_rss_link' => null ,
            '_rss_post_type' => null ,
            '_rss_post_thumbnail' => null ,
            '_rss_post_content' => null ,
            '_rss_post_author' => null ,
        ];

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
            $browser = new \Behat\Mink\Session($driver);
            $browser->start();
            $browser->visit($url);
            return $browser->getPage();
        }

        public function getElemValue($elem,$type){
            switch ($type) {
                case 'text':
                return esc_html($elem->getText());
                break;
                case 'html':
                return esc_html(strip_tags($elem->getHtml(),'<br><div><p><img>'));
                break;
                case 'inputvalue':
                return esc_html($elem->getValue());
                break;
                case 'attrsrc':
                case 'attrcontent':
                case 'attrhref':
                case 'attrname':
                $type = str_replace('attr','',$type);
                return esc_html($elem->getAttribute($type));
                break;
                default:
                return esc_html($elem->getOuterHtml());
                break;
            }
        }

        public function getRssItems($offset = 0,$limit = 10){

            $this->logger('Test','Reading RSS Feed',2);

            $feedrss = new SimplePie($this->rss);
            $links = [];
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

                if($feed){
                    foreach ([
                        '_rss_post_thumbnail' => 'Post Thumbnail',
                        '_rss_post_content' => 'Post Content',
                        '_rss_post_author' => 'Post Author',
                        ] as $field => $label) {
                            $d = $this->meta[$field];
                            $this->logger('Grab','Feed item #'.($k+1).'. Looking for '.$field,2);
                            // Lookup Type
                            switch ($d[0]) {
                                case 'XPATH':
                                $elem = $feed->find('xpath', $d[1]);
                                break;
                                case 'CSS':
                                $elem = $feed->find('css', $d[1]);
                                break;
                            }

                            if($elem !== NULL){
                                $links[$k][] = [
                                    'label' => $label,
                                    'key' => $k,
                                    'value' => $this->getElemValue($elem,$d[2])
                                ];
                                $this->logger('Grab','Feed item #'.($k+1).'. '.$field . ' found',1);
                            }
                            else {
                                $this->logger('Grab','Feed item #'.($k+1).'. '.$field . ' is empty',3);
                                $links[$k] = [
                                    'label' => $label,
                                    'key' => $k,
                                    'value' => 'Not Found'
                                ];
                            }
                        }
                    }else{
                        $this->logger('Grab','Feed item #'.($k+1).' is empty',0);
                    }
                }
                return $links;
            }

            function logger($type,$message,$level){
                $this->log[] = [
                    'status' => ['ERROR','OK','INFO','WARNING'][$level], // 0 = Error, 1 = OK , 2 = Info , 3 = Warning
                    'type' => $type,
                    'message' => $message,
                    'time' => date('Y-m-d H:i:s')
                ];
            }

            function checkIfValidRSS($url){
                $this->logger('Test','Checking RSS URL',2);
                if($this->checkUrl($url)){
                    try {
                        $rss = new SimpleXmlElement(file_get_contents($url));
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
        }
    }
