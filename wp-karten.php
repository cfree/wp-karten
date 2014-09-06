<?php

defined('ABSPATH') or die();

/**
 * @package Karten
 * @version 0.1.0
 */

/*
Plugin Name: Karten
Description: Display Instagram photos on a Google Maps map
Version: 0.1.0
Author: Craig Freeman
Author URI: http://www.craigfreeman.net
*/

// This file is part of the Karten plugin for WordPress
//
// Copyright (c) 2012-2013 Craig Freeman. All rights reserved.
// http://craigfreeman.net
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************

// Theme version
define( 'KTN_THEME_VER', '0.1.0' );
load_plugin_textdomain( 'ktn' );

// Options page
function ktn_options_setup() {
	add_options_page( 'Karten Settings', 'Karten', 'manage_options', 'ktn_opts', 'ktn_options_view' );
}

add_action( 'admin_menu', 'ktn_options_setup' );

// Options page view
function ktn_options_view() {
	?>
	<div class="wrap">
		<h2><?php _e( 'Karten Settings', 'ktn' ); ?></h2>
		<form method="post" action="options.php">
			<?php wp_nonce_field( 'ktn_options_nonce' ); ?>
			<p>
				<strong><?php _e( 'Google Maps v3 API Key', 'ktn' ); ?></strong><br />
				<input type="text" name="ktn_gmapsapi" size="45" value="<?php echo get_option( 'ktn_gmapsapi' ); ?>" />
			</p>
			<p>
				<strong><?php _e( 'Instagram API Access Token', 'ktn' ); ?></strong><br />
				<!-- TO DO: Link -->
				<input type="text" name="ktn_instagramapi" size="45" value="<?php echo get_option( 'ktn_instagramapi' ); ?>" />
			</p>
			<p>
				<strong><?php _e( 'How long to keep API cache <small>(in seconds)</small>', 'ktn' ); ?></strong><br />
				<input type="text" name="ktn_api_cache" size="45" value="<?php echo get_option( 'ktn_api_cache' ); ?>" />
			</p>

			<p>
				<input type="submit" name="Submit" class="button button-primary" value="Save Settings" />
			</p>
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="page_options" value="ktn_gmapsapi,ktn_instagramapi" />
		</form>
	</div>
	<?php
}

// Create map post type
function ktn_custom_post_type() {
	register_post_type( 'ktn_map',
		array(
			'labels' => array(
				'name' => __( 'Maps' ),
				'singular_name' => __( 'Map' )
			),
			'menu_icon' => 'dashicons-pressthis',
			'public' => true,
			'has_archive' => true,
			'publicly_queryable' => false,
			'show_in_nav_menus' => false,
			'supports' => array(
				'title'
			)
		)
	);
}

add_action( 'init', 'ktn_custom_post_type' );

// Custom columns for map post type
function ktn_custom_post_type_columns( $columns ) {
	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __( 'Map' ),
		'shortcode' => __( 'Shortcode' ),
		'id' => __( 'ID' ),
		'date' => __( 'Date' )
	);

	return $columns;
}

add_filter( 'manage_edit-ktn_map_columns', 'ktn_custom_post_type_columns' );

// Populating custom map post type columns
// Modified from: http://justintadlock.com/archives/2011/06/27/custom-columns-for-custom-post-types
function ktn_manage_custom_post_type_columns( $column, $post_id ) {
	global $post;

	switch( $column ) {
		// If displaying the 'id' column
		case 'id':
			_e( $post_id, 'ktn' );
			break;

		// If displaying the 'shortcode' column
		case 'shortcode':
			echo esc_html( '<input type="text" size="25" readonly="readonly" value=\'[karten id="' . esc_attr( $post_id ) . '"]\' />' );
			break;

		default:
			break;
	}
}

add_action( 'manage_ktn_map_posts_custom_column', 'ktn_manage_custom_post_type_columns', 10, 2 );

// Custom map post type column styles
function ktn_map_custom_post_type_admin_css() {
	echo '<style>
   	.fixed .column-id {
   		width: 10%;
   	}
   	</style>';
}

add_action('admin_head', 'ktn_map_custom_post_type_admin_css');

// Post meta setup
function ktn_meta_setup() {
	add_action( 'add_meta_boxes', 'ktn_add_meta_box' );
	add_action( 'save_post', 'ktn_save_meta', 10, 2 );
}

add_action( 'load-post.php', 'ktn_meta_setup' );
add_action( 'load-post-new.php', 'ktn_meta_setup' );

// Add post meta box
function ktn_add_meta_box() {
	add_meta_box(
		'ktn_meta',
		esc_html__( 'Karten Meta', 'ktn' ),
		'ktn_meta_box_view',
		'ktn_map',
		'normal',
		'default'
	);
}

// Post meta view
// TO DO: Make users, hashtags repeater blocks. Make start, end dates date-pickers.
function ktn_meta_box_view( $object, $box ) {
	wp_nonce_field( basename( __FILE__ ), 'ktn_meta_nonce' );

	?>
	<p>
	<!-- TO DO: Repeater -->
		<label class="req" for="ktn-meta-users"><?php _e( 'User', 'ktn' ); ?></label>
		<br />
		<input class="widefat" type="text" name="ktn-meta-users" id="ktn-meta-users" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_users', true ) ); ?>" size="30" />
	</p>
	<p>
		<label class="req" for="ktn-meta-hashtags"><?php _e( 'Hashtag (don\'t include #)', 'ktn' ); ?></label>
		<br />
		<input class="widefat" type="text" name="ktn-meta-hashtags" id="ktn-meta-hashtags" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_hashtags', true ) ); ?>" size="30" />
	</p>
	<!-- TO DO: Make date picker -->
	<p>
		a<label class="req" for="ktn-meta-start-date"><?php _e( 'Start Date', 'ktn' ); ?></label>
		<br />
		<input class="widefat" type="text" name="ktn-meta-start-date" id="ktn-meta-start-date" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_start_date', true ) ); ?>" size="30" />
	</p>
	<!-- TO DO: Make date picker -->
	<p>
		<label class="req" for="ktn-meta-end-date"><?php _e( 'End Date', 'ktn' ); ?></label>
		<br />
		<input class="widefat" type="text" name="ktn-meta-end-date" id="ktn-meta-end-date" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_end_date', true ) ); ?>" size="30" />
	</p>
	<p>
		<label class="req" for="ktn-meta-start-addr"><?php _e( 'Start Address', 'ktn' ); ?></label>
		<br />
		<input class="widefat" type="text" name="ktn-meta-start-addr" id="ktn-meta-start-addr" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_start_addr', true ) ); ?>" size="30" />
	</p>
	<p>
		<label class="req" for="ktn-meta-end-addr"><?php _e( 'End Address', 'ktn' ); ?></label>
		<br />
		<input class="widefat" type="text" name="ktn-meta-end-addr" id="ktn-meta-end-addr" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_end_addr', true ) ); ?>" size="30" />
	</p>
	<p>
		<label class="req" for="ktn-meta-max-posts"><?php _e( 'Maximum Number of Posts', 'ktn' ); ?></label>
		<br />
		<input class="widefat" type="text" name="ktn-meta-max-posts" id="ktn-meta-max-posts" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_max_posts', true ) ); ?>" size="10" />
	</p>
	<?php 
}

// Save post meta
function ktn_save_meta( $post_id, $post ) {
	// Verify the nonce before proceeding
	if ( ! isset( $_POST['ktn_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ktn_meta_nonce'], basename( __FILE__ ) ) ) {
		return $post_id;
	}

	// Get the post type object
	$post_type = get_post_type_object( $post->post_type );

	// Check if the current user has permission to edit the post
	if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	// Get the posted data and sanitize it for use as an HTML class
	$new_meta_value = ( isset( $_POST['smashing-post-class'] ) ? sanitize_html_class( $_POST['smashing-post-class'] ) : '' );

	/* Get the meta key. */
	$meta_key = 'smashing_post_class';

	/* Get the meta value of the custom field key. */
	$meta_value = get_post_meta( $post_id, $meta_key, true );

	/* If a new meta value was added and there was no previous value, add it. */
	if ( $new_meta_value && '' == $meta_value )
		add_post_meta( $post_id, $meta_key, $new_meta_value, true );

	/* If the new meta value does not match the old value, update it. */
	elseif ( $new_meta_value && $new_meta_value != $meta_value )
		update_post_meta( $post_id, $meta_key, $new_meta_value );

	/* If there is no new meta value but an old value exists, delete it. */
	elseif ( '' == $new_meta_value && $meta_value )
		delete_post_meta( $post_id, $meta_key, $meta_value );
}

// Are the admin settings complete?
function ktn_opts_set() {
	// Google Maps API Key
	if ( get_option( 'ktn_gmapsapi' ) ) {
		define( 'KTN_GMAPS_KEY', get_option( 'ktn_gmapsapi') );
	}
	// Instagram API Access Token
	if ( get_option( 'ktn_instagramapi' ) ) {
		define( 'KTN_INSTAGRAM_TOKEN', get_option( 'ktn_instagramapi' ) );
	}

	if ( defined( 'KTN_GMAPS_KEY' ) && defined( 'KTN_INSTAGRAM_TOKEN' ) ) {
		return true;
	}
	else {
		return false;
	}
}

// Is there related meta on this page?
// function ktn_post_has_meta() {
// 	global $post;
// 	$page_id = $post(get_the_ID());

// 	// Get meta
// }

// Enqueue scripts/styles in template header
function ktn_assets() {
	// Is the page worthy of loading the assets? (Are options entered and is meta on the page?)
	if ( ktn_opts_set() && ktn_post_has_meta() ) {
		// Scripts
		wp_enqueue_script( 'ktn_google_maps', 'https://maps.googleapis.com/maps/api/js?key=' . KTN_GMAPS_KEY . '&sensor=false', array(), KTN_THEME_VER, false );
		wp_enqueue_script( 'ktn_script', plugins_url( '/scripts.js', __FILE__ ), array( 'jquery', 'ktn_google_maps', 'ktn_google_maps_old' ), KTN_THEME_VER, true );

		// Stylesheets	
		wp_register_style( 'ktn_style', plugins_url( '/style.css', __FILE__ ), array(), KTN_THEME_VER );
		wp_enqueue_style( 'ktn_style' );
	}
}

add_action( 'wp_enqueue_scripts', 'ktn_assets' );

/**
 * DEPRECATED
 **/
function showMap() {
	echo file_get_contents( dirname( __FILE__ ) . '/map-template.php' );
}

/**
 * Display a map
 **/
// function ktn_show_map($args = false) {
// 	// TO DO: Find proper way to handle args
// 	if (!$args) {
// 		// Defaults
// 		$args = array(
// 			'users' => array('openapple','pfflyer'), // array (or string?)
// 			'hashtags' => array('move2012'), // array (or string?)
// 			'start_date' => '', // undetermined
// 			'end_date' => '', // undetermined
// 			'max_posts' => 999, // int
// 			'height' => 300, // px int % str
// 			'width' => '100%', // px int or % str
// 			'max_width' => 'auto', // auto str, px int or % str
// 			'start_coord' => array(lat, long), // array(lat float, lng float)
// 			'end_coord' => array(lat, long), // array(lat float, lng float)
// 			//'center_point' => array(), // empty, array(lat float, lng float) // TO DO
// 			//'bounds' => array(), // empty, array(lat float, lng float) // TO DO
// 			//'zoom_level' => 'auto' // auto fit around start_coord, end_coord or int 1-14 around center_point // TO DO
// 		);
// 	}
// }

/**
 * Shortcode to display a map
 **/
function ktn_show_map_shortcode() {
	// Check for on page meta
}

//add_filters('', 'ktn_show_map_shortcode');