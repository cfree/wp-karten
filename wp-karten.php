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

// Options page setup
function ktn_options_setup() {
	// Create new settings section
	add_options_page( 'Karten Settings', 'Karten', 'administrator', 'ktn_opts', 'ktn_options_view' );

	// Call register settings function
	add_action( 'admin_init', 'ktn_register_settings' );
}

add_action( 'admin_menu', 'ktn_options_setup' );

// Options page settings
function ktn_register_settings() {
    add_settings_section(
        'ktn_opts_api_keys',
        'API Keys',
        'ktn_opts_api_keys_callback',
        'ktn_opts'
    );

    // Google Maps v3 API key
    add_settings_field(
		'ktn_gmapsapi',
		'Google Maps v3 API Key',
		'ktn_gmapsapi_input_callback',
		'ktn_opts',
		'ktn_opts_api_keys'
	);

	register_setting(
		'ktn_settings_group',
		'ktn_gmapsapi'
	);

	// Instagram API access token
	add_settings_field(
		'ktn_instagramapi',
		'Instagram API Access Token',
		'ktn_instagramapi_input_callback',
		'ktn_opts',
		'ktn_opts_api_keys'
	);

	register_setting(
		'ktn_settings_group',
		'ktn_instagramapi'
	);

	// API cache
	add_settings_field(
		'ktn_api_cache',
		'How long to keep API cache <small>(in seconds)</small>',
		'ktn_api_cache_input_callback',
		'ktn_opts',
		'ktn_opts_api_keys'
	);

	register_setting(
		'ktn_settings_group',
		'ktn_api_cache'
	);
}

function ktn_opts_api_keys_callback() {
	_e( '<p>Enter your API information here.</p>', 'ktn' );
}

function ktn_options_view() {
	// Check that the user is allowed to update options
	if ( !current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}

	?>
		<div class="wrap">
			<h2><?php _e( 'Karten Settings', 'ktn' ); ?></h2>
			<form method="post" action="options.php">
				<?php
					do_settings_sections( 'ktn_opts' );

					settings_fields( 'ktn_settings_group' );

					submit_button();
				?>
			</form>
		</div>
	<?php
}

function ktn_gmapsapi_input_callback() {
	?>
		<input type="text" name="ktn_gmapsapi" id="ktn_gmapsapi" size="45" value="<?php echo get_option( 'ktn_gmapsapi' ); ?>" />
	<?php
}

function ktn_instagramapi_input_callback() {
	?>
		<input type="text" name="ktn_instagramapi" id="ktn_instagramapi" size="45" value="<?php echo get_option( 'ktn_instagramapi' ); ?>" />
	<?php
}

function ktn_api_cache_input_callback() {
	?>
		<input type="text" name="ktn_api_cache" id="ktn_api_cache" size="20" value="<?php echo get_option( 'ktn_api_cache' ); ?>" />
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
function ktn_manage_custom_post_type_columns( $column, $post_id ) {
	global $post;

	switch ( $column ) {
		// If displaying the 'id' column
		case 'id':
			_e( $post_id, 'ktn' );
			break;

		// If displaying the 'shortcode' column
		case 'shortcode':
			_e( '<input type="text" size="25" readonly="readonly" value=\'[karten id="' . esc_attr( $post_id ) . '"]\' />', 'ktn' );
			break;

		default:
			break;
	}
}

add_action( 'manage_ktn_map_posts_custom_column', 'ktn_manage_custom_post_type_columns', 10, 2 );

// Admin styles, scripts
function ktn_admin_scripts_styles( $hook ) {
	global $post_type;

	// Is the post type `ktn_map`? Are we on the editor page?
	if ( ! is_admin() || $post_type != 'ktn_map' || 'edit.php' != $hook ) {
		return;
	}

	// Enqueue admin-columns.css on admin list page
	if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'ktn_map' ) {
		wp_enqueue_style( 'karten-admin-columns', plugin_dir_url( __FILE__ ) . 'assets/css/admin-columns.css' );
	}
}

add_action( 'admin_enqueue_scripts', 'ktn_admin_scripts_styles' );

// Admin editor scripts
function ktn_admin_edit_scripts() {
	global $post_type;

	// Is the post type `ktn_map`?
	if ( ! is_admin() || $post_type != 'ktn_map') {
		return;
	}
	
	// Enqueue admin-edit.js on map custom post type editor page
	wp_enqueue_script( 'karten-admin-edit', plugin_dir_url( __FILE__ ) . 'assets/js/admin-edit.js', array( 'jquery-ui-datepicker' ), '1.0.0', true );
}

add_action( 'admin_print_scripts-post.php', 'ktn_admin_edit_scripts', 11);
add_action( 'admin_print_scripts-post-new.php', 'ktn_admin_edit_scripts', 11 );

// Admin editor styles
function ktn_admin_edit_styles() {
	global $post_type;

	// Is the post type `ktn_map`?
	if ( ! is_admin() || $post_type != 'ktn_map') {
		return;
	}
	
	// Enqueue admin-edit.css on map custom post type editor page
	wp_enqueue_style( 'jquery-ui', plugin_dir_url( __FILE__ ) . 'assets/css/jquery-ui/jquery-ui.theme.css' );
	wp_enqueue_style( 'karten-admin-edit', plugin_dir_url( __FILE__ ) . 'assets/css/admin-edit.css' );
}

add_action( 'admin_print_styles-post.php', 'ktn_admin_edit_styles', 11);
add_action( 'admin_print_styles-post-new.php', 'ktn_admin_edit_styles', 11 );

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
		esc_html__( 'Map Settings', 'ktn' ),
		'ktn_meta_box_view',
		'ktn_map',
		'normal',
		'default'
	);
}

// Post meta view
// TO DO: Make users, hashtags repeater blocks
function ktn_meta_box_view( $object, $box ) {
	wp_nonce_field( 'ktn_save_meta', 'ktn_meta_nonce' );

	?>
	<!-- TO DO: Repeater -->
	<p>
		<label class="req" for="ktn-meta-users"><?php _e( 'User', 'ktn' ); ?></label>
		<br />
		<input class="widefat" type="text" name="ktn-meta-users" id="ktn-meta-users" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_users', true ) ); ?>" size="30" />
	</p>
	<p>
		<label class="req" for="ktn-meta-hashtags"><?php _e( 'Hashtag <small>(don\'t include #)</small>', 'ktn' ); ?></label>
		<br />
		<input class="widefat" type="text" name="ktn-meta-hashtags" id="ktn-meta-hashtags" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_hashtags', true ) ); ?>" size="30" />
	</p>
	<p>
		<label class="req" for="ktn-meta-start-date"><?php _e( 'Start Date', 'ktn' ); ?></label>
		<br />
		<input size="20" type="text" name="ktn-meta-start-date" id="ktn-meta-start-date" class="datepicker" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_start_date', true ) ); ?>" size="30" />
	</p>
	<p>
		<label class="req" for="ktn-meta-end-date"><?php _e( 'End Date', 'ktn' ); ?></label>
		<br />
		<input size="20" type="text" name="ktn-meta-end-date" id="ktn-meta-end-date" class="datepicker" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_end_date', true ) ); ?>" size="30" />
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
		<input size="20" type="number" min="0" name="ktn-meta-max-posts" id="ktn-meta-max-posts" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_max_posts', true ) ); ?>" size="10" />
	</p>
	<?php 
}

// Save post meta
function ktn_save_meta( $post_id, $post ) {
	// Verify the nonce before proceeding
	if ( ! isset( $_POST['ktn_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ktn_meta_nonce'], 'ktn_save_meta' ) ) {
		return $post_id;
	}

	if ( $post->post_type != 'ktn_map' || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return $post_id;
	}

	// Get the post type object
	$post_type = get_post_type_object( $post->post_type );

	// Check if the current user has permission to edit the post
	if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
		return $post_id;
	}

	// Form field names mapped to meta names
	$meta_keys = array(
		// form field name => meta name
		'ktn-meta-users' => 'ktn_meta_users',
		'ktn-meta-hashtags' => 'ktn_meta_hashtags',
		'ktn-meta-start-date' => 'ktn_meta_start_date',
		'ktn-meta-end-date' => 'ktn_meta_end_date',
		'ktn-meta-start-addr' => 'ktn_meta_start_addr',
		'ktn-meta-end-addr' => 'ktn_meta_end_addr',
		'ktn-meta-max-posts' => 'ktn_meta_max_posts'
	);

	// Set new meta
	foreach ($meta_keys as $field => $meta) {
		ktn_set_meta( $post_id, $meta, $field );
	}
}

/**
 * Get Maps post meta
 */
function ktn_get_meta() {
	// ktn-meta-users
	// ktn-meta-hashtags
	// ktn-meta-start-date
	// ktn-meta-end-date
	// ktn-meta-start-addr
	// ktn-meta-end-addr
	// ktn-meta-max-posts
}

/**
 * Set Maps post meta
 */
function ktn_set_meta( $post_id, $meta_value, $meta_key ) {
	// Get the posted data and sanitize it for use as an HTML class
	$new_meta_value = ( isset( $_POST[$new_meta_value_string] ) ? $_POST[sanitize_html_class( $new_meta_value_string )] : '' );

	// Get the meta key
	$meta_value = get_post_meta( $post_id, $meta_key, true );

	// If a new meta value was added and there was no previous value, add it
	if ( $new_meta_value && '' == $meta_value ) {
		add_post_meta( $post_id, $meta_key, $new_meta_value, true );
	}
	// If the new meta value does not match the old value, update it
	else if ( $new_meta_value && $new_meta_value != $meta_value ) {
		update_post_meta( $post_id, $meta_key, $new_meta_value );
	}
	// If there is no new meta value but an old value exists, delete it
	else if ( '' == $new_meta_value && $meta_value ) {
		delete_post_meta( $post_id, $meta_key, $meta_value );
	}
}

// Are the admin settings set?
function ktn_get_opts() {
	if ( is_defined( 'KARTEN_GMAPS_API_KEY' ) && is_defined( 'KARTEN_INSTAGRAM_API_KEY' ) ) {
		return array(
			KARTEN_GMAPS_API_KEY,
			KARTEN_INSTAGRAM_API_KEY
		);
	}
	
	return false;
}

/**
 * Set constants right after WordPress core is loaded
 */
function ktn_set_opts() {
	if ( $gmaps = get_option( 'ktn_gmapsapi' ) && $instagram = get_option( 'ktn_instagramapi' ) ) {
		if ( ! is_defined( 'KARTEN_GMAPS_API_KEY' ) && ! is_defined( 'KARTEN_INSTAGRAM_API_KEY' ) ) {
			define( 'KARTEN_GMAPS_API_KEY', $gmaps );
			define( 'KARTEN_INSTAGRAM_API_KEY', $instagram );
		}
	}
}

add_action( 'init', 'ktn_set_opts' );

// Is there related Map meta on this page?
function ktn_get_post_meta() {
	global $post;

	// Get meta
	$meta_values = get_post_meta( get_the_ID(), 'ktn_meta' );

	// Get map post IDs
	// Get meta of map post ID
	// Return array of URL parameter-ready items for each map post ID

	// Otherwise...
	return false;
}

// Enqueue scripts/styles in template header
function ktn_assets() {
	// Is the page worthy of loading the assets? (Are options entered and is meta on the page?)
	if ( is_defined( 'KARTEN_INSTAGRAM_API_KEY' ) && is_defined( 'KARTEN_GMAPS_API_KEY' ) && $maps_meta = ktn_get_post_meta() && is_array( $meta ) ) {
		// Stylesheets	
		wp_register_style( 'ktn_style', plugins_url( '/assets/css/style.css', __FILE__ ), array(), KTN_THEME_VER );
		wp_enqueue_style( 'ktn_style' );

		// Build API queries
		$urls = array();

		foreach ( $maps_meta as $map_meta ) {
			$urls[] = ktn_get_query_url( $map_meta );
		}

		// Scripts
		wp_enqueue_script( 'ktn_google_maps', 'https://maps.googleapis.com/maps/api/js?key=' . $opts[0] . '&sensor=false', array(), KTN_THEME_VER, false );
		wp_register_script( 'ktn_scripts', plugins_url( '/assets/js/scripts.js', __FILE__ ), array( 'jquery', 'ktn_google_maps' ), KTN_THEME_VER, true );
		wp_localize_script( 'ktn_scripts', 'KARTEN', $urls );
		wp_enqueue_script( 'ktn_scripts' );
	}
}

add_action( 'wp_enqueue_scripts', 'ktn_assets' );

/**
 * Construct Instagram API URL
 */
function ktn_get_query_url( $parameters ) {
	// @TO-DO: Translate Instagram username to ID
	$user_id = '2575810';
	$base_url = 'https://api.instagram.com/v1/users/' . $user_id . '/media/recent?access_token=' . KARTEN_INSTAGRAM_API_KEY;

	// array ('count'=>30, 'min_timestamp'=>1352527200, 'max_timestamp'=>1354514400 );
	// count=30&min_timestamp=1352527200&max_timestamp=1354514400
	$query_string = http_build_query( $parameters );

	return $base_url . $query_string;
}

/**
 * @DONE: Implement shortcode
 * @DONE: Implement template tag
 * @TO-DO: Prepare map post meta to have URL constructed
 * @TO-DO: Construct URL
 * @TO-DO: Get Instagram user ID
 * @TO-DO: Enqueue scripts only when needed
 *    - Comb post for short code pre-save, add meta array of associated Map post IDs?
 *    - Check meta for map post IDs when loading page, create URLs and localize, enqueue scripts/styles?
 * @TO-DO: Tie short code to scripts
 * @TO-DO: Object orientify
 * @TO-DO: Use PHPDoc comment formatting: http://make.wordpress.org/core/handbook/inline-documentation-standards/php-documentation-standards/
 * @TO-DO: Help Section (how to get Google Maps API, how to create Instagram client & how to get Instagram API access token, explain cache)
 * @TO-DO: Decide on license
 * @TO-DO: Update README
 * @TO-DO: Create WP README
 * @TO-DO: Review plugin standards, adhere
 * @TO-DO: Create website explaining plugin
 * @TO-DO: Code review
 */


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
function ktn_show_map_shortcode_handler( $atts ) {
	$has_id = ( is_array( $atts ) && ! empty( $atts['id'] ) ) ? true : false;

	if ( $has_id ) {
		print_r('has id');
		ktn_get_map( $atts['id'] );
	}
}

add_shortcode( 'karten', 'ktn_show_map_shortcode_handler' );

/**
 * Template tag to display a map
 **/
function ktn_map( $id ) {
	echo ktn_get_map( $id );
}

/**
 * Template tag to return a map
 **/
function ktn_get_map( $id ) {
	// Is the ID numeric? Check for on page meta, options
	if ( is_numeric( $id ) && ktn_get_opts() && ktn_get_post_meta() ) {
		// Return map wrapper
		return '<div class="map-canvas" data-karten-id="' . $id .'"></div><!-- Karten map -->';
	}
}
