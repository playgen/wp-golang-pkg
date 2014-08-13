<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class Golang_Pkg_Table extends WP_List_Table {

	private $table = '';

	/**
	 * Constructor, we override the parent to pass our own arguments
	 * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
	 */
	function __construct() {
		parent::__construct( array(
			'singular' => 'golang_pkg', // Singular label
			'plural'   => 'golang_pkgs', // plural label, also this well be one of the table css class
			'ajax'     => false // We won't support Ajax for this table
		) );
	}

	function get_columns() {
		return array(
			'cb'      => '<input type="checkbox" />',
			'slug'    => __( 'Package Slug', 'golang_pkg' ),
			'type'    => __( 'Repository Type', 'golang_pkg' ),
			'url'     => __( 'Repository URL', 'golang_pkg' ),
			'enabled' => __( 'Enabled', 'golang_pkg' )
			);
	}

	function get_sortable_columns() {
		return array(
			// 'ID' => array( 'ID', false ),
			'slug'  => array( 'slug', false ),
			'type'  => array( 'type', true ),
			'url'   => array( 'url', true ),
		);
	}

	function prepare_items()
	{
		global $wpdb;

		$table = golangpkg_pkg_table();

		// Pagination Args
		$totalitems = $wpdb->get_var("SELECT count(`ID`) FROM {$table}");
		$perpage = $this->get_items_per_page( 'gpkgs_per_page', 10 );
		// This is done as early as possible because it might result in a redirect.
		$this->set_pagination_args( array(
			"total_items" => $totalitems,
			"per_page" => $perpage,
		) );

		$query = "SELECT * FROM {$table}";

		// Dr Search
		if ( ! empty( $_GET['s'] ) ) {
			$query .= $wpdb->prepare(' WHERE `slug` LIKE %s', '%' . $_GET['s'] . '%');
		}

		// Ordering
		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = mysql_real_escape_string( $_GET['orderby'] );
			$order = isset( $_GET['order'] ) && strtolower( $_GET['order'] ) == 'desc' ? 'desc' : 'asc';
			$query .= " ORDER BY {$orderby} {$order}";
		}

		// Actual Pagination
		$paged = $this->get_pagenum();
		$offset = ( $paged - 1 ) * $perpage;
		$query .= " LIMIT {$offset}, {$perpage}";

		// Items
		$this->items = $wpdb->get_results( $query );
	}

	function get_bulk_actions()
	{
		return array(
			'disable' => 'Disable',
			'enable'  => 'Enable',
			'delete'  => 'Delete Permanently'
		);
	}
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="link[]" value="%s" />', $item->ID
		);
	}

	function column_slug( $item )
	{
		// Use bulk- just so the damn thing works
		$url = wp_nonce_url( "?page={$_REQUEST['page']}&link={$item->ID}", 'bulk-' . $this->_args['plural'] );
		$link = '<a href="'. $url .'&action=%s">%s</a>';
		$actions = array();
		if ( $item->enabled != 0 )
			$actions['disable'] = sprintf( $link, 'disable', "Disable" );
		else
			$actions['enable'] = sprintf( $link, 'enable', "Enable" );
		$actions['delete'] = sprintf( $link, 'delete',  "Delete Permanently"  );
		return $item->slug . ' ' . $this->row_actions( $actions );
	}
	function column_enabled( $item )
	{
		return $item->enabled != 0 ? 'yes' : 'no';
	}

	function column_default( $item, $column )
	{
		return $item->{$column};
	}

}

