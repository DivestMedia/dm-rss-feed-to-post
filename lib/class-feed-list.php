<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Feed_List_Table extends WP_List_Table {
	/** Class constructor */
	public function __construct(){
		parent::__construct([
			'singular' => __( 'Feed' ), //singular name of the listed records
			'plural'   => __( 'Feeds' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		]);
	}
	/**
	* Retrieve customers data from the database
	*
	* @param int $per_page
	* @param int $page_number
	*
	* @return mixed
	*/
	public static function get_customers( $per_page = 5, $page_number = 1 ) {
		global $wpdb;

		global $table_prefix;
		$table  = $table_prefix . 'subscribenow';

		$sql = "SELECT * FROM $table";
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}
		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		return $result;
	}
	/**
	* Delete a customer record.
	*
	* @param int $id customer ID
	*/
	public static function delete_customer( $id ) {
		global $wpdb;
		global $table_prefix;
		$table  = $table_prefix . 'subscribenow';

		return $wpdb->delete( "$table", [ 'id' => $id ], [ '%d' ] );
	}
	/**
	* Returns the count of records in the database.
	*
	* @return null|string
	*/
	public static function record_count() {
		global $wpdb;
		global $table_prefix;
		$table  = $table_prefix . 'subscribenow';
		$sql = "SELECT COUNT(*) FROM $table";
		return $wpdb->get_var( $sql );
	}
	/** Text displayed when no customer data is available */
	public function no_items() {
		_e( 'No subscribers yet.', 'sp' );
	}
	/**
	* Render a column when no column specific method exist.
	*
	* @param array $item
	* @param string $column_name
	*
	* @return mixed
	*/
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			default:
			return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}
	/**
	* Render the bulk edit checkbox
	*
	* @param array $item
	*
	* @return string
	*/
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id'] );
	}
	/**
	*  Associative array of columns
	*
	* @return array
	*/
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			// 'email'    => __( 'Email Address', 'sp' ),
			// 'fullname'    => __( 'Full Name', 'sp' ),
			// 'displayname' => __( 'Nickname', 'sp' ),
			// 'contact' => __( 'Contact', 'sp' ),
			// 'updated_at' => __( 'Last Updated', 'sp' ),
			// 'status' => __( 'Status', 'sp' ),
		];
		return $columns;
	}
	/**
	* Columns to make sortable.
	*
	* @return array
	*/
	public function get_sortable_columns() {
		$sortable_columns = array(
			'email' => array( 'email', true ),
			'fullname' => array( 'fullname', false ),
		);
		return $sortable_columns;
	}
	/**
	* Returns an associative array containing the bulk action
	*
	* @return array
	*/
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => 'Delete'
		];
		return $actions;
	}
	/**
	* Handles data query and filter, sorting, and pagination.
	*/


	public function prepare_items() {
		$this->_column_headers = $this->get_column_info();
		/** Process bulk action */
		$this->process_bulk_action();
		$per_page     = $this->get_items_per_page( 'customers_per_page', 5 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();
		$this->set_pagination_args([
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		]);
		$this->items = self::get_customers( $per_page, $current_page );
	}
	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			// // In our file that handles the request, verify the nonce.
			// $nonce = $_REQUEST['sp_delete_customer'];
			// if ( ! wp_verify_nonce( $nonce, 'delete_customer' ) ) {
			// 	die( 'Go get a life script kiddies' );
			// }
			// else {
			// 	if(!self::delete_customer( absint( $_GET['customer'] ) )){;
			// 		$url = add_query_arg([
			// 			'page' => 'subscribe-now',
			// 			'notice' => 'delete-failed'
			// 		],site_url() . parse_url($_SERVER["REQUEST_URI"])['path']);
			// 	}else{
			// 		$url = add_query_arg([
			// 			'page' => 'subscribe-now',
			// 			'notice' => 'delete-success'
			// 		],site_url() . parse_url($_SERVER["REQUEST_URI"])['path']);
			// 	}
			// 	// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
			// 	// add_query_arg() return the current url
			// 	if (headers_sent()) {
			// 		echo "<script>window.location.assign('". $url ."')</script>";
			// 	}else{
			// 		wp_redirect( $url );
			// 	}
			// 	exit;
			// }
		}
		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		|| ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {
			$delete_ids = esc_sql( $_POST['bulk-delete'] );
			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				self::delete_customer( $id );
			}
			// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
			// add_query_arg() return the current url
			wp_redirect( esc_url_raw(add_query_arg()) );
			exit;
		}
	}
	public function column_email($item){
		$actions = array(
			'delete' => sprintf( '<a href="?page=%s&action=%s&customer=%s&sp_delete_customer=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), wp_create_nonce( 'delete_customer' ) )
		);
		return sprintf('%1$s %2$s', $item['email'], $this->row_actions($actions) );
	}
	public function column_status($item){
		$actions = array(
			'resend'    => sprintf('<a href="?page=%s&action=%s&email=%s">Resend Email</a>',$_REQUEST['page'],'resend',$item['email']),
		);
		if($item['status'] == 2)
		return sprintf('%1$s %2$s',  ['Blocked','Verified','Pending'][$item['status']], $this->row_actions($actions) );
		else
		return sprintf('%1$s', ['Blocked','Verified','Pending'][$item['status']]);
	}

}
