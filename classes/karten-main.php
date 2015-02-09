<?php

defined('ABSPATH') or die();

/**
 * @package Karten
 * @version 1.0.0
 */

class KartenMain {
	/**
	 * Constructor
	 */
	public function __construct() {
		// Actions
		add_action( 'admin_enqueue_scripts', array( $this, 'ktn_admin_scripts_styles' ) );
		add_action( 'admin_print_scripts-post.php', array( $this, 'ktn_admin_edit_scripts' ), 11 );
		add_action( 'admin_print_scripts-post-new.php', array( $this, 'ktn_admin_edit_scripts' ), 11 );
		add_action( 'admin_print_styles-post.php', array( $this, 'ktn_admin_edit_styles' ), 11);
		add_action( 'admin_print_styles-post-new.php', array( $this, 'ktn_admin_edit_styles' ), 11 );
		add_action( 'ktn_show_map', array( $this, 'ktn_map' ), 10, 1 );

		// Shortcodes
		add_shortcode( 'karten', array( $this, 'ktn_show_map_shortcode_handler' ) );
	}

	/**
	 * Admin styles, scripts
	 */
	public function ktn_admin_scripts_styles( $hook ) {
		global $post_type;

		// Is the post type `ktn_map`? Are we on the editor page?
		if ( ! is_admin() || $post_type != 'ktn_map' || 'edit.php' != $hook ) {
			return;
		}

		// Enqueue admin-columns.css on admin list page
		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'ktn_map' ) {
			wp_enqueue_style( 'karten-admin-columns', plugins_url( 'assets/css/admin-columns.css', dirname( __FILE__ ) ) );
		}
	}

	/**
	 * Admin editor scripts
	 */
	public function ktn_admin_edit_scripts() {
		global $post_type;

		// Is the post type `ktn_map`?
		if ( ! is_admin() || $post_type != 'ktn_map') {
			return;
		}
		
		// Enqueue admin-edit.js on map custom post type editor page
		wp_enqueue_script( 'karten-admin-edit', plugins_url( 'assets/js/admin-edit.js', dirname( __FILE__ ) ), array( 'jquery-ui-datepicker' ), KTN_THEME_VER, true );
	}

	/**
	 * Admin editor styles
	 */
	public function ktn_admin_edit_styles() {
		global $post_type;

		// Is the post type `ktn_map`?
		if ( ! is_admin() || $post_type != 'ktn_map') {
			return;
		}
		
		// Enqueue jQuery UI on map custom post type editor page
		wp_enqueue_style( 'jquery-ui', plugins_url( 'assets/css/jquery-ui/jquery-ui.theme.css' , dirname( __FILE__ ) ) );
	}

	/**
	 * Enqueue scripts/styles in template header
	 */
	public function ktn_enqueue_assets( $id ) {
		// Stylesheets
		if ( ! wp_script_is( 'ktn_scripts', 'enqueued' ) ) {
			wp_enqueue_style( 'ktn_style', plugins_url( '/assets/css/style.css', dirname( __FILE__ ) ), array(), KTN_THEME_VER );
		}

		// Build API queries
		if ( $params = $this::ktn_query_params( $id ) ) {
			// Scripts
			if ( ! wp_script_is( 'ktn_scripts', 'enqueued' ) ) {
				wp_enqueue_script( 'ktn_google_maps', '//maps.googleapis.com/maps/api/js?key=' . KARTEN_GMAPS_API_KEY, array(), KTN_THEME_VER, false );
				wp_register_script( 'ktn_scripts', plugins_url( '/assets/js/scripts.js', dirname( __FILE__ ) ), array( 'jquery', 'ktn_google_maps' ), KTN_THEME_VER, true );
			}

			wp_localize_script( 'ktn_scripts', 'KartenData' . $id, $params );
			wp_enqueue_script( 'ktn_scripts' );
		}
	}

	/**
	 * Retrieve Instagram API parameters
	 */
	public function ktn_query_params( $id ) {
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
				
		// Hashtag
		if ( ! empty( $map_meta['ktn_meta_hashtags'] ) ) {
			$parameters['hashtags'] = $map_meta['ktn_meta_hashtags'][0];
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
		$parameters['api_keys'] = KartenSetup::ktn_get_opts();

		return $parameters;
	}

	/**
	 * Shortcode to display a map
	 **/
	public function ktn_show_map_shortcode_handler( $atts, $content = null ) {
		$has_id = ( is_array( $atts ) && ! empty( $atts['id'] ) ) ? true : false;

		if ( $has_id ) {
			return $this::ktn_get_map( $atts['id'] );
		}
	}

	/**
	 * Template action hook to display a map
	 **/
	public function ktn_map( $id ) {
		echo $this::ktn_get_map( $id );
	}

	/**
	 * Retrieve a map
	 **/
	public function ktn_get_map( $id ) {
		// Is the ID numeric? Check for on page meta, options
		if ( ! empty( $id ) && is_numeric( $id ) && KartenSetup::ktn_get_opts() ) {
			$this::ktn_enqueue_assets( $id );

			// Return map wrapper
			return '<div class="ktn-wrapper"><div class="ktn-map-canvas" data-ktn-id="' . esc_attr( $id ) . '"></div></div><!-- Karten map -->';
		}
		else {
			return '';
		}
	}
}

new KartenMain;
