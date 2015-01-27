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
// Copyright (c) 2012-2014 Craig Freeman. All rights reserved.
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

/**
 * Theme version
 */
define( 'KTN_THEME_VER', '0.1.0' );
load_plugin_textdomain( 'ktn' );

/**
 * Options page setup
 */
function ktn_options_setup() {
	// Create new settings section
	add_options_page( 'Karten Settings', 'Karten', 'administrator', 'ktn_opts', 'ktn_options_view' );

	// Call register settings function
	add_action( 'admin_init', 'ktn_register_settings' );
}

add_action( 'admin_menu', 'ktn_options_setup' );

/**
 * Options page settings
 */
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

	// Instagram API client ID
	add_settings_field(
		'ktn_instagramapi_client_id',
		'Instagram API Client ID',
		'ktn_instagramapi_client_id_input_callback',
		'ktn_opts',
		'ktn_opts_api_keys'
	);

	register_setting(
		'ktn_settings_group',
		'ktn_instagramapi_client_id'
	);

	// Instagram API client secret
	add_settings_field(
		'ktn_instagramapi_client_secret',
		'Instagram API Client Secret',
		'ktn_instagramapi_client_secret_input_callback',
		'ktn_opts',
		'ktn_opts_api_keys'
	);

	register_setting(
		'ktn_settings_group',
		'ktn_instagramapi_client_secret'
	);

	// Instagram API access token
	add_settings_field(
		'ktn_instagramapi_token',
		'Instagram API Access Token',
		'ktn_instagramapi_token_input_callback',
		'ktn_opts',
		'ktn_opts_api_keys'
	);

	register_setting(
		'ktn_settings_group',
		'ktn_instagramapi_token'
	);
}

function ktn_opts_api_keys_callback() {
	_e( '<p>Enter your API information here.</p>', 'ktn' );
}

function ktn_options_view() {
	// Check that the user is allowed to update options
	if ( ! current_user_can( 'manage_options' ) ) {
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
		<a href="https://code.google.com/apis/console" target="blank"><?php _e( 'Need an API key?', 'ktn' ); ?></a>
	<?php
}

function ktn_instagramapi_client_id_input_callback() {
	?>
		<input type="text" name="ktn_instagramapi_client_id" id="ktn_instagramapi_client_id" size="45" value="<?php echo get_option( 'ktn_instagramapi_client_id' ); ?>" />
		<a href="http://instagram.com/developer/clients/manage/" target="_blank"><?php _e( 'Need a Client ID?', 'ktn' ); ?></a>
	<?php
}

function ktn_instagramapi_client_secret_input_callback() {
	?>
		<input type="text" name="ktn_instagramapi_client_secret" id="ktn_instagramapi_client_secret" size="45" value="<?php echo get_option( 'ktn_instagramapi_client_secret' ); ?>" />
	<?php
}

function ktn_instagramapi_token_input_callback() {
	$token = get_option( 'ktn_instagramapi_token' );
	$response = isset( $_GET['code'] ) ? $_GET['code'] : false;
	$saved = ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == true ) ? $_GET['settings-updated'] : false;
	$redirect_url = trailingslashit( admin_url() ) . 'options-general.php?page=ktn_opts';
	$encoded_url = urlencode( $redirect_url );
	$val = '';
	$client_id = get_option( 'ktn_instagramapi_client_id' );
	$client_secret = get_option( 'ktn_instagramapi_client_secret' );

	// No token saved or API response?
	if ( ! $token ) : ?>
		<div id="message" class="updated fade">
			<h3><?php _e( 'Steps to get Instagram API access:', 'ktn' ); ?></h3>
			<ol>
				<li><?php _e( 'Click the "Need a Client ID?" link below and login to Instagram', 'ktn' ); ?></li>
				<li><?php _e( 'Register a new client and use the following as the REDIRECT URI', 'ktn' ); ?>: <code><?php echo $redirect_url; ?></code></li>
				<li><?php _e( 'Copy new CLIENTS\'s ID and CLIENT\'s SECRET into the fields below', 'ktn' ); ?></li>
				<li><?php _e( 'Click the "Save Changes" button', 'ktn' ); ?></li>
				<li><?php _e( 'Click the "Need an Access Token?" link and log into Instagram', 'ktn' ); ?></li>
				<li><?php _e( 'Copy the Access Token returned and enter it into the Access Token field below', 'ktn' ); ?></li>
				<li><?php _e( 'Click the "Save Changes" button', 'ktn' ); ?></li>
			</ol>
		</div>
	<?php endif;

	// API has responded with temp code to get access key
	if ( $response && ! $saved && $client_id && $client_secret ) {
		$url = "https://api.instagram.com/oauth/access_token";
		$access_token_parameters = array(
			'client_id' => $client_id,
			'client_secret' => $client_secret,
			'grant_type' => 'authorization_code',
			'redirect_uri' => $redirect_url,
			'code' => $response,
		);

		$curl = curl_init( $url );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $access_token_parameters );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		
		$result = curl_exec( $curl );
		curl_close( $curl );

		$result = json_decode( $result, true );

		if ( $result && ! empty( $result ) && isset( $result['error_message'] ) ) {
			?>
				<div id="message" class="error fade">
					<p><?php _e( sprintf( 'There\'s been an error: %s', esc_html( $result['error_message'] ) ) ); ?></p>
				</div>
			<?php
		} else if ( $result && ! empty( $result ) ) {
			$given_token = isset( $result['access_token'] ) ? $result['access_token'] : false;
		}

		// API has responded with access key
		if ( $given_token ) : ?>
			<div id="message" class="updated fade">
				<p>
					<?php _e( sprintf( 'Your Access Token is: <code>%s</code>', esc_html( $given_token ) ), 'ktn' ); ?><br/>
					<?php _e( 'Be sure to copy this into the Access Token field below and save the settings.', 'ktn' ); ?>
				</p>
			</div>
		<?php endif;
	}

	if ( $client_id ) : ?>
			<input type="text" name="ktn_instagramapi_token" id="ktn_instagramapi_token" size="45" value="<?php echo $token; ?>" />
	<?php
		echo '<a href="https://api.instagram.com/oauth/authorize/?client_id=' . $client_id . '&redirect_uri=' . $encoded_url . '&response_type=code">' . __( 'Need an Access Token?', 'ktn' ) . '</a>';
	endif;
}

/**
 * Create map post type
 */
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
				'title',
			)
		)
	);
}

add_action( 'init', 'ktn_custom_post_type' );

/**
 * Custom columns for map post type
 */
function ktn_custom_post_type_columns( $columns ) {
	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __( 'Map' ),
		'shortcode' => __( 'Shortcode' ),
		'id' => __( 'ID' ),
		'date' => __( 'Date' ),
	);

	return $columns;
}

add_filter( 'manage_edit-ktn_map_columns', 'ktn_custom_post_type_columns' );

/**
 * Populating custom map post type columns
 */
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

/**
 * Admin styles, scripts
 */
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

/**
 * Admin editor scripts
 */
function ktn_admin_edit_scripts() {
	global $post_type;

	// Is the post type `ktn_map`?
	if ( ! is_admin() || $post_type != 'ktn_map') {
		return;
	}
	
	// Enqueue admin-edit.js on map custom post type editor page
	wp_enqueue_script( 'karten-admin-edit', plugin_dir_url( __FILE__ ) . 'assets/js/admin-edit.js', array( 'jquery-ui-datepicker' ), '1.0.0', true );
}

add_action( 'admin_print_scripts-post.php', 'ktn_admin_edit_scripts', 11 );
add_action( 'admin_print_scripts-post-new.php', 'ktn_admin_edit_scripts', 11 );

/**
 * Admin editor styles
 */
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

/**
 * Post meta setup
 */
function ktn_meta_setup() {
	add_action( 'add_meta_boxes', 'ktn_add_meta_box' );
	add_action( 'save_post', 'ktn_save_meta', 10, 2 );
}

add_action( 'load-post.php', 'ktn_meta_setup' );
add_action( 'load-post-new.php', 'ktn_meta_setup' );

/**
 * Add post meta box
 */
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

/**
 * Post meta view
 */
function ktn_meta_box_view( $object, $box ) {
	wp_nonce_field( 'ktn_save_meta', 'ktn_meta_nonce' );

	?>
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

/**
 * Save post meta
 */
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
 * Set Maps post meta
 */
function ktn_set_meta( $post_id, $meta_value, $meta_key ) {
	// Get the posted data and sanitize it for use as an HTML class
	$new_meta_value = ( isset( $_POST[$new_meta_value_string] ) ? $_POST[ sanitize_html_class( $new_meta_value_string ) ] : '' );

	// Get the meta key
	$meta_value = get_post_meta( $post_id, $meta_key, true );

	// If a new meta value was added and there was no previous value, add it
	if ( $new_meta_value && '' == $meta_value ) {
		add_post_meta( $post_id, $meta_key, $new_meta_value, true );
	}
	// If there is no new meta value but an old value exists, delete it
	else if ( empty( $new_meta_value ) && $meta_value ) {
		delete_post_meta( $post_id, $meta_key, $meta_value );
	}
	// If the new meta value does not match the old value, update it
	else if ( $new_meta_value && $new_meta_value != $meta_value ) {
		update_post_meta( $post_id, $meta_key, $new_meta_value );
	}
}

/**
 * Are the admin settings set?
 */
function ktn_get_opts() {
	if ( defined( 'KARTEN_GMAPS_API_KEY' ) && defined( 'KARTEN_INSTAGRAM_API_KEY' ) ) {
		return array(
			'maps' => KARTEN_GMAPS_API_KEY,
			'instagram' => KARTEN_INSTAGRAM_API_KEY,
		);
	}
	
	return false;
}

/**
 * Set constants right after WordPress core is loaded
 */
function ktn_set_opts() {
	if ( $gmaps = get_option( 'ktn_gmapsapi' ) && $instagram = get_option( 'ktn_instagramapi_token' ) ) {
		if ( ! defined( 'KARTEN_GMAPS_API_KEY' ) && ! defined( 'KARTEN_INSTAGRAM_API_KEY' ) ) {
			define( 'KARTEN_GMAPS_API_KEY', $gmaps );
			define( 'KARTEN_INSTAGRAM_API_KEY', $instagram );
		}
	}
}

add_action( 'init', 'ktn_set_opts' );

/**
 * Enqueue scripts/styles in template header
 */
function ktn_enqueue_assets( $id ) {
	// Stylesheets
	wp_enqueue_style( 'ktn_style', plugins_url( '/assets/css/style.css', __FILE__ ), array(), KTN_THEME_VER );

	// Build API queries
	if ( $params = ktn_query_params( $id ) ) {
		// Scripts
		wp_enqueue_script( 'ktn_google_maps', '//maps.googleapis.com/maps/api/js?key=' . KARTEN_GMAPS_API_KEY . '&sensor=false', array(), KTN_THEME_VER, false );
		wp_register_script( 'ktn_scripts', plugins_url( '/assets/js/scripts.js', __FILE__ ), array( 'jquery', 'ktn_google_maps' ), KTN_THEME_VER, true );
		wp_localize_script( 'ktn_scripts', 'KartenData' . $id, $params );
		wp_enqueue_script( 'ktn_scripts' );
	}
}

/**
 * Construct Instagram API URL
 */
function ktn_query_params( $id ) {
	// Get meta related to ID
	$map_meta = get_post_meta( $id );

	// Create API query string
	$parameters = array();

	// Add map ID
	$parameters['id'] = $id;

	// Users (comma-delimited)
	if ( ! empty( $map_meta['ktn_meta_users'] ) ) {
		$parameters['usernames'] = explode( ',', $map_meta['ktn_meta_users'][0] );
	}
			
	// Hashtag (comma-delimited)
	if ( ! empty( $map_meta['ktn_meta_hashtags'] ) ) {
		$parameters['hashtags'] = explode( ',', $map_meta['ktn_meta_hashtags'][0] );
	}

	// Start date
	if ( ! empty( $map_meta['ktn_meta_start_date'] ) ) {
		$parameters['start_date'] = strtotime( $map_meta['ktn_meta_start_date'][0] );
	}

	// End date
	if ( ! empty( $map_meta['ktn_meta_end_date'] ) ) {
		$parameters['end_date'] = strtotime( $map_meta['ktn_meta_end_date'][0] );
	}

	// Start addr
	if ( ! empty( $map_meta['ktn_meta_start_addr'] ) ) {
		$parameters['start_addr'] = $map_meta['ktn_meta_start_addr'][0];
	}
	
	// End addr
	if ( ! empty( $map_meta['ktn_meta_end_addr'] ) ) {
		$parameters['end_addr'] = $map_meta['ktn_meta_end_addr'][0];
	}

	// Max posts
	if ( ! empty( $map_meta['ktn_meta_max_posts'] ) ) {
		$parameters['max_posts'] = intval( $map_meta['ktn_meta_max_posts'][0] );
	}

	// API keys
	$parameters['api_keys'] = ktn_get_opts();

	return $parameters;
}

/**
 * @DONE: Implement shortcode
 * @DONE: Implement template tag
 * @DONE: Enqueue scripts only when needed
 *    - Comb post for short code pre-save, add meta array of associated Map post IDs?
 *    - Check meta for map post IDs when loading page, create URLs and localize, enqueue scripts/styles?
 *    - Enqueue scripts at time of shortcode processing
 * @DONE: Tie short code to scripts
 * @DONE: Make it easier to get API settings
 * @TO-DO: Reformat JS
 * @TO-DO: Use OOJS for multiple maps on 1 page
 * @TO-DO: Prepare map post meta to have URL constructed
 * @TO-DO: Construct URLs
 * @TO-DO: Get Instagram user IDs
 * @TO-DO: Determine which could be private vs public variable, update
 * @TO-DO: Object orientify
 * @TO-DO: Use PHPDoc comment formatting: http://make.wordpress.org/core/handbook/inline-documentation-standards/php-documentation-standards/
 * @DONE: Decide on license: GPL
 * @DONE: Update README (how to get Google Maps API, how to create Instagram client & how to get Instagram API access token, explain cache)
 * @TO-DO: Code review
 */

/**
 * Shortcode to display a map
 **/
function ktn_show_map_shortcode_handler( $atts ) {
	$has_id = ( is_array( $atts ) && ! empty( $atts['id'] ) ) ? true : false;

	if ( $has_id ) {
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
	if ( is_numeric( $id ) && ktn_get_opts() ) {
		ktn_enqueue_assets( $id );

		// Return map wrapper
		echo '<div class="ktn-wrapper"><div class="ktn-map-canvas" data-ktn-id="' . esc_attr( $id ) .'"></div></div><!-- Karten map -->';
	}
}
