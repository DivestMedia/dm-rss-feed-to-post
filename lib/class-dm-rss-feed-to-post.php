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
                ],
                '_rss_post_type' => [
                    'label' => 'Post Type',
                    'description' => 'Optional. Default: Post',
                    'type' => 'text',
                ],
                '_rss_post_thumbnail' => [
                    'label' => 'Post Thumbnail',
                    'description' => '',
                    'type' => 'grabber',
                ],
                '_rss_post_content' => [
                    'label' => 'Post Content',
                    'description' => '',
                    'type' => 'grabber',
                ],
                '_rss_post_author' => [
                    'label' => 'Post Author',
                    'description' => '',
                    'type' => 'grabber',
                ],
            ]
        ];
        public $browser = null;

        function __CONSTRUCT()
        {
            add_action('init', [&$this, 'init']);
            add_action('admin_init', [&$this, 'admin_init']);
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
        }

        public function show_meta_box($post,$meta){
            $rss_data  = [];
            foreach ($this->option_fields[$meta['args']['group']] as $field => $data) {
                $this->option_fields[$meta['args']['group']][$field]['value'] = get_post_meta( $post->ID, $field, true );
            }
            wp_nonce_field( basename( __FILE__ ), '_'.$meta['args']['group'].'_metabox_nonce' );
            include_once( DM_RSS_PLUGIN_DIR . 'partials/metabox_'.$meta['args']['group'].'.php');
        }

        public function save_meta_box(){

            global $post;
            if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
                return;
            }
            if( ! current_user_can( 'edit_post', $post->id ) ){
                return;
            }

            foreach ($this->option_fields as $group => $fields) {
                if( !isset( $_POST['_'.$group.'_metabox_nonce'] ) || !wp_verify_nonce( $_POST['_'.$group.'_metabox_nonce'], basename( __FILE__ ) ) ){
                    return;
                }
                foreach ($fields as $key => $data) {
                    $this->save_meta_value($post->ID,$key,stripslashes_deep($_POST[$key]));
                }
            }
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
                    'view_item' => 'View RSS Feed',
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
                'taxonomies' => ['rss_category']
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

        public function activate(){ }
        public function deactivate(){ }
    }

}
