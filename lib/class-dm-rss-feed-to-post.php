<?php if(!defined('DM_RSS_VERSION')) die('Fatal Error');

/*
* Divest Media RSS Feed to Post Main Class File
*/
if(!class_exists('DMRSS')){
    class DMRSS
    {
        public $option_fields = [
            'rss_details' => [
                '_rss_link' => [
                    'label' => 'Feed URL',
                    'description' => 'URL where the feeds will be fetched',
                    'type' => 'text',
                    'required' => true
                ],
                '_rss_post_type' => [
                    'label' => 'Post Type',
                    'description' => 'Optional. Default: Post',
                    'type' => 'text',
                    'required' => false
                ],
                '_rss_post_category' => [
                    'label' => 'Post Category',
                    'description' => 'Optional. Separate each by comma. Default: Uncategorized',
                    'type' => 'text',
                    'required' => false
                ],
                '_rss_post_published' => [
                    'label' => 'Published Date',
                    'description' => 'Optional. Default: Current timestamp',
                    'type' => 'grabber',
                    'required' => false
                ],
                '_rss_post_thumbnail' => [
                    'label' => 'Post Thumbnail',
                    'description' => '',
                    'type' => 'grabber',
                    'required' => true
                ],
                '_rss_post_content' => [
                    'label' => 'Post Content',
                    'description' => '',
                    'type' => 'grabber',
                    'required' => true
                ],
                '_rss_post_author' => [
                    'label' => 'Post Author',
                    'description' => '',
                    'type' => 'grabber',
                    'required' => false
                ],
                '_rss_post_meta' => [
                    'label' => 'Custom Meta',
                    'description' => '',
                    'type' => 'grabber',
                    'required' => false
                ],
                '_rss_post_ignore' => [
                    'label' => 'Exclude Content via CSS',
                    'description' => 'Take note that css queries will start searching not from the root but from each of the equivalent HTML type elements above. Separate queries by line. ',
                    'type' => 'textarea',
                    'required' => false
                ],
                '_rss_post_tags' => [
                    'label' => 'Post Tags',
                    'description' => 'Assign Post Tags if some of the provided keywords were found inside the article content.',
                    'type' => 'keywords',
                    'required' => false
                ],
            ]
        ];
        public $browser = null;

        function __CONSTRUCT()
        {
            add_action('init', [&$this, 'init']);
            add_action('admin_init', [&$this, 'admin_init']);
            add_action('grab_feeds', [&$this, 'grab_feeds']);
            add_filter('cron_schedules', [&$this, 'rss_intervals']);
            add_filter('manage_rss_feed_posts_columns' , [&$this, 'manage_rss_feed_posts_columns_cb']);
            add_filter('manage_rss_feed_posts_custom_column' , [&$this, 'manage_rss_feed_posts_custom_column_cb'],10,2);
        }

        public function init(){
            $this->register_rss_post_type();
            $this->register_rss_meta_boxes();
        }

        public function admin_init(){

            $this->register_rss_ajax('feed_check');


            wp_enqueue_script( 'rss-feed-to-post-js', DM_RSS_PLUGIN_URL . 'assets/admin.js',['jquery']);
        }

        public function register_rss_meta_boxes(){
            add_action( 'add_meta_boxes', function(){
                add_meta_box( 'rss_details', 'Feed Details', [&$this, 'show_meta_box'], 'rss_feed' , 'normal', 'high',['group'=>'rss_details']);
            });
            add_action( 'save_post_rss_feed', [ &$this , 'save_meta_box' ]);
            add_action( 'before_delete_post', [ &$this , 'delete_post' ]);
        }

        public function show_meta_box($post,$meta){
            $rss_data  = [];
            foreach ($this->option_fields[$meta['args']['group']] as $field => $data) {
                $this->option_fields[$meta['args']['group']][$field]['value'] = get_post_meta( $post->ID, $field, true );
            }
            wp_nonce_field( basename( __FILE__ ), '_'.$meta['args']['group'].'_metabox_nonce' );
            include_once( DM_RSS_PLUGIN_DIR . 'partials/metabox_'.$meta['args']['group'].'.php');
        }

        function delete_post($postid){
            global $post_type;
            if ( $post_type != 'rss_feed' ) return;

            wp_unschedule_event( wp_next_scheduled( 'grab_feeds', ['id' => $postid ]  ), 'grab_feeds', ['id' => $postid ] );
        }

        public function save_meta_box(){

            global $post;
            if(empty($post) || !isset($post->ID)){
                return;
            }

            if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
                return;
            }
            if( ! current_user_can( 'edit_post', $post->ID ) ){
                return;
            }

            foreach ($this->option_fields as $group => $fields) {
                if( !isset( $_POST['_'.$group.'_metabox_nonce'] ) || !wp_verify_nonce( $_POST['_'.$group.'_metabox_nonce'], basename( __FILE__ ) ) ){
                    return;
                }
                foreach ($fields as $key => $data) {
                    if($key=='_rss_post_tags'){
                        $postdata = $_POST[$key];
                        foreach ($postdata as $postkey => $dataarray) {
                            foreach ($dataarray as $datakey => $datavalue) {
                                if(empty($datavalue)){
                                    unset($postdata['meta'][$datakey]);
                                    unset($postdata['type'][$datakey]);
                                    unset($postdata['query'][$datakey]);
                                    unset($postdata['selector'][$datakey]);
                                }
                            }
                        }
                        $_POST[$key] = $postdata;
                    }
                    if($key=='_rss_post_meta'){
                        $postdata = $_POST[$key];
                        foreach ($postdata as $postkey => $dataarray) {
                            foreach ($dataarray as $datakey => $datavalue) {
                                if(empty($datavalue)){
                                    unset($postdata['meta'][$datakey]);
                                    unset($postdata['type'][$datakey]);
                                    unset($postdata['query'][$datakey]);
                                    unset($postdata['selector'][$datakey]);
                                }
                            }
                        }
                        $_POST[$key] = $postdata;
                    }
                    $this->save_meta_value($post->ID,$key,stripslashes_deep($_POST[$key]));
                }
            }

            if (! wp_next_scheduled ( 'grab_feeds' ,['id' => $post->ID ])) {
                wp_schedule_event(time(), 'minutes_5', 'grab_feeds',['id' => $post->ID ]);
            }

        }

        public function grab_feeds($id){
            $RSSMink = new RSSMink($id);
            $this->logger('Grab Feeds','Start',2);
            $feeds = $RSSMink->getRssItems(0,10);
            foreach ($feeds as $key => $feeddata) {

                $this->logger('Grab Feeds','Preparing Data',2);
                $defaults = [
                    'post_status' => 'publish',
                    'post_type' => 'post',
                ];
                $itemurl = '';
                $itemauthor = '';
                $itemtags = [];
                var_dump($feeddata);
                break;
                foreach ($feeddata as $key => $data) {
                    if(substr( $data['key'], 0, 5 ) === "meta-"){
                        if(!isset($args['meta_input'])) $args['meta_input'] = [];
                        $args['meta_input'][substr( $data['key'], 5)] = $data['value'];
                    }
                    elseif(substr( $data['key'], 0, 5 ) === "tags-"){
                        if(!isset($args['meta_input'])) $args['meta_input'] = [];
                        $newtag = ucwords(substr( $data['key'], 5));
                        switch ($data['value']['validate']) {
                            case 'one':
                            if((int)$data['value']['found'] > 0){
                                $itemtags[] = $newtag;
                            }
                            break;
                            case 'none':
                            if((int)$data['value']['found'] < 1){
                                $itemtags[] = $newtag;
                            }
                            break;
                            case 'all':
                            default:
                            if((int)$data['value']['found'] == count($data['value']['keywords'])){
                                $itemtags[] = $newtag;
                            }
                            break;
                        }

                        // $args['meta_input'][substr( $data['key'], 5)] = $data['value'];
                    }else{
                        switch ($data['key']) {
                            case 'post-title':
                            $args['post_title'] = $data['value'];
                            break;
                            case 'published-date':
                            $args['post_date'] = date('Y-m-d H:i:s',strtotime($data['value']));
                            break;
                            case 'post-thumbnail':
                            $args['post-thumbnail'] = $data['value'];
                            break;
                            case 'post-excerpt':
                            $args['post_excerpt'] = $data['value'];
                            break;
                            case 'post-content':
                            $args['post_content'] = $data['value'];
                            break;
                            case 'post-url':
                            $itemurl = $data['value'];
                            break;
                            case 'post-author':
                            $itemauthor = $data['value'];
                            break;
                        }
                    }
                }
                if ( ! function_exists( 'post_exists' ) ) {
                    require_once( ABSPATH . 'wp-admin/includes/post.php' );
                }
                if(!post_exists($args['post_title'])){

                    $this->logger('Grab Feeds','Creating New Post',2);
                    $cat_post_meta = get_post_meta($id,'_rss_post_category',true);
                    if(!empty($cat_post_meta)){
                        $catid = [];
                        $cats = explode(',',$cat_post_meta);
                        foreach ($cats as $key => $value) {
                            $catobj = get_term_by('name',$value,'category');
                            if(!empty($catobj)){
                                $catid[] = $catobj->term_id;
                            }else{
                                if(!function_exists('wp_create_category')){
                                    require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
                                }
                                if($idhere = wp_create_category($value)){
                                    $catid[] = $idhere;
                                };
                            }
                        }
                        if(!empty($catid)){
                            $args['post_category'] = $catid;
                        }
                    }

                    $args = wp_parse_args($args, $defaults);

                    if(($insertid = wp_insert_post($args))>0){
                        $this->logger('Grab Feeds','Post Creation Success . ID : ' . $insertid,1);
                        add_post_meta($insertid, 'dm_rss_feed_id', $id, true);
                        add_post_meta($insertid, 'dm_rss_feed_item_link', $itemurl, true);
                        if(!empty($itemauthor)){
                            add_post_meta($insertid, 'dm_rss_feed_item_author', $itemauthor, true);
                        }

                        if(!empty($itemtags)){
                            wp_set_post_tags( $insertid, $itemtags, true );
                        }

                        $this->grab_thumbnail($args['post-thumbnail'],$insertid);
                    }else{
                        $this->logger('Grab Feeds','Post Creation Failed',0);
                    }
                }else{
                    $this->logger('Grab Feeds','Post Already Exist',2);
                }

            }

        }


        function rss_intervals($interval) {
            $interval['minutes_5'] = array('interval' => 5*60, 'display' => 'Once every 5 minutes');
            return $interval;
        }

        public function save_meta_value($id,$meta_id = '',$value = ''){
            if(!empty($meta_id)){
                if( isset( $value ) ){
                    update_post_meta( $id , $meta_id , $value );
                }else{
                    delete_post_meta( $id , $meta_id  );
                }
            }
        }

        public function register_rss_post_type(){

            register_post_type( 'rss_feed',[
                'labels' => [
                    'name' => 'RSS Feed',
                    'singular_name' => 'RSS Feed',
                    'add_new' => 'Add New RSS Feed',
                    'add_new_item' => 'Add New RSS Feed',
                    'edit_item' => 'Edit RSS Feed',
                    'new_item' => 'Add New RSS Feed',
                    'search_items' => 'Search RSS Feed',
                    'not_found' => 'No feeds found',
                    'not_found_in_trash' => 'No feeds found in trash'
                ],
                'public' => true,
                'capability_type' => 'post',
                'has_archive' => false,
                'menu_icon' => 'dashicons-media-document',
                'rewrite' => [
                    'slug' => 'rss-feed'
                ],
                'supports' => [
                    'title',
                    'thumbnail',
                ],
                'taxonomies' => ['rss_category'],
                'show_in_admin_bar'   => false,
                'show_in_nav_menus'   => false,
                'publicly_queryable'  => false,
                'query_var'           => false
            ]);

            register_taxonomy( 'rss_category',  'rss_feed', [
                'hierarchical' => true,
                'label' => 'Feed Category',  //Display name
                'labels' => 	[
                    'name'              => 'Feed Category',
                    'singular_name'     => 'Feed Category',
                    'search_items'      => 'Search Categories',
                    'all_items'         => 'All Categories',
                    'parent_item'       => 'Parent Category',
                    'parent_item_colon' => 'Parent Category:',
                    'edit_item'         => 'Edit Category',
                    'update_item'       => 'Update Category',
                    'add_new_item'      => 'Add New Category',
                    'new_item_name'     => 'New Category',
                    'menu_name'         => 'Feed Category',
                ],
                'public' => false,
                'publicly_queryable' => true,
                'exclude_from_search' => false,
                'query_var' => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'capabilities' => [
                    'manage_terms',
                    'edit_terms',
                    'delete_terms',
                    'assign_terms',
                ],
                'rewrite' =>[
                    'slug' => 'rss-category', // This controls the base slug that will display before each term
                    'with_front' => false // Don't display the category base before
                ]
            ]);

            flush_rewrite_rules();
        }

        public function register_rss_ajax($action = 'default'){

            add_action( 'wp_ajax_nopriv_rss_' . $action , [&$this, 'rss_ajax_' . $action . '_cb']);
            add_action( 'wp_ajax_rss_' . $action , [&$this, 'rss_ajax_' . $action . '_cb' ]);

        }

        public function rss_ajax_feed_check_cb(){
            $stats = [];
            if(isset($_POST['id'])){
                $RSSMink = new RSSMink($_POST['id']);
                $stats = ([
                    'status' => 1,
                    'message' => 'Ajax Called Successfully',
                    'data' => $RSSMink->getRssItems(0,1),
                    'log' => $RSSMink->log,
                ]);
            }else{
                $stats = ([
                    'status' => 0,
                    'message' => 'No URL Given'
                ]);
            }

            exit(json_encode($stats));
        }

        function rss_ajax_default_cb(){
            return 0;
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


        public function grab_thumbnail( $image_url, $post_id , $thumbnail = true ){
            $upload_dir = wp_upload_dir();

            $opts = [
                'http' => [
                    'method'  => 'GET',
                    'user_agent '  => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36",
                    'header' => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8
                        '
                    ]
                ]
            ];

            $context  = stream_context_create($opts);

            // $image_data = file_get_contents($image_url,false,$context);
            $image_data = $this->file_get_contents_curl($image_url);
            if(!is_array(getimagesize($image_url))){
                return false;
            }

            $filename = basename($image_url);

            // Remove Query Strings
            $querypos = strpos($filename, '?');
            if($querypos!==FALSE){
                $filename = substr($filename,0,$querypos);
            }
            if(wp_mkdir_p($upload_dir['path']))     $file = $upload_dir['path'] . '/' . $filename;
            else                                    $file = $upload_dir['basedir'] . '/' . $filename;
            file_put_contents($file, $image_data);

            $wp_filetype = wp_check_filetype($filename, null );
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name($filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
            $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
            if($thumbnail){
                $res2= set_post_thumbnail( $post_id, $attach_id );
            }else{
                return $attach_id;
            }
        }

        public static function activate(){
            $pubfeeds = get_posts([
                'post_type'   => 'rss_feed',
                'numberposts' => -1,
                'post_status' => 'publish'
            ]);

            foreach ($pubfeeds as $key => $post) {
                if (! wp_next_scheduled ( 'grab_feeds' ,['id' => $post->ID ])) {
                    wp_schedule_event(time(), 'minutes_5', 'grab_feeds',['id' => $post->ID ]);
                }
            }

        }
        public static function deactivate(){
            $hook = 'grab_feeds';
            $crons = _get_cron_array();
            if ( empty( $crons ) ) {
                return;
            }
            foreach( $crons as $timestamp => $cron ) {
                if ( ! empty( $cron[$hook] ) )  {
                    unset( $crons[$timestamp][$hook] );
                }

                if ( empty( $crons[$timestamp] ) ) {
                    unset( $crons[$timestamp] );
                }
            }
            _set_cron_array( $crons );
        }

        function manage_rss_feed_posts_columns_cb($columns) {

            $new_columns = array(
                'import_count' => __('Imported Posts'),
                'next_import' => 'Next Import'
            );
            return array_merge($columns, $new_columns);
        }

        function manage_rss_feed_posts_custom_column_cb($column, $post_id){
            switch ( $column ) {
                case 'import_count' :
                $query = new WP_Query( array( 'meta_key' => 'dm_rss_feed_id', 'meta_value' => $post_id ) );
                echo $query->found_posts?: 0;
                break;
                case 'next_import' :
                if((isset($_GET['rss_action']) && $_GET['rss_action']=='import-now') &&
                (isset($_GET['rss_id']) && $_GET['rss_id']==$post_id)){
                    if ($nextime = wp_next_scheduled ( 'grab_feeds' ,['id' => $post_id ])) {
                        wp_unschedule_event($nextime,  'grab_feeds' ,['id' => $post_id ] );
                        if(wp_reschedule_event(time(), 'minutes_5', 'grab_feeds',['id' => $post_id ])==null){
                            echo '<script>window.location.assign("'.add_query_arg( array(
                                'rss_action' => 'import-success',
                                'rss_id' => $post_id,
                            )).'")</script>';
                        };
                    }
                }

                if((isset($_GET['rss_action']) && $_GET['rss_action']=='import-success') &&
                (isset($_GET['rss_id']) && $_GET['rss_id']==$post_id)){
                    echo 'Import Rescheduled' . '<br>';
                }
                $timestamp = wp_next_scheduled(  'grab_feeds' ,['id' => $post_id ]);
                echo human_time_diff($timestamp + (get_option( 'gmt_offset' ) * 3600) ) . ' from now' ?: 'Not scheduled yet';
                echo '<br>';
                echo '<div class="row-actions"><span class="run-now"><a href="'.add_query_arg( array(
                    'rss_action' => 'import-now',
                    'rss_id' => $post_id,
                )).'">Import Now</a></span></div>';
                break;
            }
        }

        function logger($type,$message,$level){
            $lognow = [
                'status' => ['ERROR','OK','INFO','WARNING'][$level], // 0 = Error, 1 = OK , 2 = Info , 3 = Warning
                'type' => $type,
                'message' => $message,
                'time' => date('Y-m-d H:i:s')
            ];
            $this->log[] = $lognow;

            $logfile = fopen(DM_RSS_PLUGIN_DIR . "dmrss-feeds.log", "a") or die("Unable to open file!");
            $txt = JSON_ENCODE($lognow)."\n";
            fwrite($logfile, $txt);
            fclose($logfile);
        }
    }

}
