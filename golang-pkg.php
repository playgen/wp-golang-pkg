<?php
/*
Plugin Name: Go Package Directory
Description: Allows you to import your go packages via your site's URL
Author: Playgen
Author URI: http://playgen.com
Plugin URI: https://github.com/playgenhub/wp-golang-pkg
License: GPLv2
Version: 1.0.0


Copyright 2014 Playgen Ltd (playgen.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

$golangpkg_table = null;

function golangpkg_lookup_pkg( $slug )
{
	global $wpdb;
	$table = golangpkg_pkg_table();
	return $wpdb->get_row( $wpdb->prepare( "SELECT `slug`, `type`, `url` FROM {$table} WHERE `slug` = %s", $slug ) );
}

function golangpkg_pkg_table()
{
	global $wpdb;
	return $wpdb->prefix . 'golang_pkgs';
}

function golangpkg_pkg_create()
{
	global $wpdb;
	if ( empty( $_POST['slug'] ) || empty( $_POST['url'] ) || empty( $_POST['type'] ) )
		wp_die( "Missing required parameter!", "Could not create link!", array( 'response' => 400, 'back_link' => true ) );

	$url = $_POST['url'];
	if ( filter_var( $url, FILTER_VALIDATE_URL ) === FALSE )
		wp_die( "{$url} is not a URL!", "Could not create link!", array( 'response' => 400, 'back_link' => true ) );

	$slug = $_POST['slug'];
	if ( sanitize_title_with_dashes( $slug, '', 'save' ) != $slug )
		wp_die( "Invalid slug!", "Could not create link!", array( 'response' => 400, 'back_link' => true ) );

	$type = $_POST['type'];
	if ( $type != 'bzr' && $type != 'git' && $type != 'hg' && $type != 'svn' )
		wp_die( "Invalid VCS!", "Could not create link!", array( 'response' => 400, 'back_link' => true ) );

	$exists = golangpkg_lookup_pkg( $slug );
	if ( $exists !== NULL )
		wp_die( "That slug already maps to {$exists}!", "Could not create link!", array( 'response' => 400, 'back_link' => true ) );

	$wpdb->insert( golangpkg_pkg_table(), array(
		'slug'    => $slug,
		'url'     => $url,
		'type'    => $type,
		'enabled' => true,
	), array( '%s', '%s', '%s', '%d' ) );
}

function golangpkg_pkg_enabled( $id, $status )
{
	global $wpdb;
	$wpdb->update(
		golangpkg_pkg_table(),
		array(
			'enabled' => $status
		),
		array(
			'ID' => $id
		),
		array(
			'%d'
		)
	);
}

function golangpkg_pkg_disable( $id )
{
	golangpkg_pkg_enabled( $id, false );
}
function golangpkg_pkg_enable( $id )
{
	golangpkg_pkg_enabled( $id, true );
}
function golangpkg_pkg_delete( $id )
{
	global $wpdb;
	$wpdb->delete(
		golangpkg_pkg_table(),
		array(
			'ID' => $id
		)
	);
}

function golangpkg_table_actions()
{
	global $golangpkg_table;
	$action = $golangpkg_table->current_action();
	if ( ! $action )
		return;
	// These actions don't require anything happening
	if ( 'new' == $action || 'edit' == $action )
		return;
	elseif ( 'post-new' == $action ) {
		check_admin_referer( 'new-' . $golangpkg_table->_args['singular'] );
		return golangpkg_pkg_create();
	}

	if ( empty( $_GET['link'] ) )
		return;

	check_admin_referer( 'bulk-' . $golangpkg_table->_args['plural'] );

	$providers = (array) $_GET['link']; // Abuse that (array) "2" == array("2")
	if ( 'disable' == $action )
		array_walk($providers, 'golangpkg_pkg_disable');
	elseif ( 'enable' == $action )
		array_walk($providers, 'golangpkg_pkg_enable');
	elseif ( 'delete' == $action )
		array_walk($providers, 'golangpkg_pkg_delete');
}

function golangpkg_menu_page_render()
{
	global $golangpkg_table;
	$action = $golangpkg_table->current_action();
	if ( $action == 'new' || $action == 'edit' )
		include "golang-pkg-options-page-editor.php";
	else
		include "golang-pkg-options-page-table.php";
}

function golangpkg_menu_page_setup()
{
	global $golangpkg_table;

	require 'class-golang-pkg-table.php';
	$golangpkg_table = new Golang_Pkg_Table();
	golangpkg_table_actions();
	$golangpkg_table->prepare_items();
	add_screen_option( 'per_page', array(
		'label' => 'Link',
		'default' => 10,
		'option' => 'gpkgs_per_page'
	) );
}

function _golangpkg_init()
{
	global $wp_rewrite;
	add_rewrite_tag( '%golang_pkg%', '(.+?)' );

	add_rewrite_rule(
		'pkg/(.+)/?$',
		'index.php?golang_pkg=$matches[1]&p=-1',
		'top'
	);
}
add_action( 'init', '_golangpkg_init' );

function _golangpkg_parse_query( $query )
{
	$slug = $query->get( 'golang_pkg' );
	if ( empty( $slug ) )
		return;

	if ( empty( $_GET['go-get'] ) )
		return;

	$pkg = golangpkg_lookup_pkg( $slug );
	if ( $pkg == NULL )
		return;

	$here = "{$_SERVER['HTTP_HOST']}/pkg/{$pkg->slug}";
	$vcs = $pkg->type;
	$url = $pkg->url;
	$meta = "$here $vcs $url";
	header('content-type: application/xhtml+xml; charset=utf-8');
	echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head><meta name="go-import" content="<?php echo $meta; ?>"></head>
<body></body></html>
<?php
	die;
}
add_action( 'parse_query', '_golangpkg_parse_query' );

function _golangpkg_admin_menu()
{
	$id = add_management_page( "Go Packages", "Go Packages", 'manage_options', 'golang-pkgs', 'golangpkg_menu_page_render');
	add_action( "load-{$id}", 'golangpkg_menu_page_setup' );
}
add_action( 'admin_menu', '_golangpkg_admin_menu' );
function _golangpkg_set_screen_option( $status, $option, $value )
{
	if ( 'gpkgs_per_page' == $option )
		return $value;
	return $status;
}
add_filter( 'set-screen-option', '_golangpkg_set_screen_option', 10, 3);


function _golangpkg_activation_hook()
{
	global $wpdb, $wp_rewrite;
	$table_name = golangpkg_pkg_table();
	$q = <<<SQL
CREATE TABLE IF NOT EXISTS $table_name (
	`ID`      INT(11)      NOT NULL AUTO_INCREMENT,
	`slug`    VARCHAR(75)  NOT NULL,
	`type`    CHAR(3)      NOT NULL,
	`url`     VARCHAR(256) NOT NULL,
	`enabled` BOOLEAN      NOT NULL DEFAULT TRUE,
	PRIMARY KEY (`ID`),
	UNIQUE INDEX `sluglookup` (`slug`)
) DEFAULT CHARSET=utf8;
SQL;
	$wpdb->query($q);
	$wp_rewrite->flush_rules( false );
}
register_activation_hook( __FILE__, '_golangpkg_activation_hook' );
