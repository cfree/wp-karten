<?php

defined('ABSPATH') or die();

/**
 * @package Karten
 * @version 0.1.0
 */

class KartenSetup {
	/**
	 * Constructor
	 */ 
	public function __construct() {
		// Actions
		add_action( 'init', array( $this, 'ktn_custom_post_type' ) );
		add_action( 'init', array( $this, 'ktn_set_opts' ) );
		add_action( 'manage_ktn_map_posts_custom_column', array( $this, 'ktn_manage_custom_post_type_columns' ), 10, 2 );
		add_action( 'load-post.php', array( $this, 'ktn_meta_setup' ) );
		add_action( 'load-post-new.php', array( $this, 'ktn_meta_setup' ) );
		add_action( 'admin_menu', array( $this, 'ktn_options_setup' ) );

		// Filters
		add_filter( 'manage_edit-ktn_map_columns', array( $this, 'ktn_custom_post_type_columns' ) );
	}

	/**
	 * Options page setup
	 */
	public function ktn_options_setup() {
		// Create new settings section
		add_submenu_page( 'edit.php?post_type=ktn_map', 'Settings', 'Settings', 'manage_options', 'ktn_opts', array( $this, 'ktn_options_view' ) );

		// Call register settings function
		add_action( 'admin_init', array( $this, 'ktn_register_settings' ) );
	}

	/**
	 * Options page settings
	 */
	public function ktn_register_settings() {
		add_settings_section(
			'ktn_opts_api_keys',
			'API Keys',
			array( $this, 'ktn_opts_api_keys_callback' ),
			'ktn_opts'
		);

		// Google Maps v3 API key
		add_settings_field(
			'ktn_gmapsapi',
			'Google Maps v3 API Key',
			array( $this, 'ktn_gmapsapi_input_callback' ),
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
			array( $this, 'ktn_instagramapi_client_id_input_callback' ),
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
			array( $this, 'ktn_instagramapi_client_secret_input_callback' ),
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
			array( $this, 'ktn_instagramapi_token_input_callback' ),
			'ktn_opts',
			'ktn_opts_api_keys'
		);

		register_setting(
			'ktn_settings_group',
			'ktn_instagramapi_token'
		);
	}

	public function ktn_opts_api_keys_callback() {
		_e( '<p>Enter your API information here.</p>', 'ktn' );
	}

	public function ktn_options_view() {
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

	public function ktn_gmapsapi_input_callback() {
		?>
			<input type="text" name="ktn_gmapsapi" id="ktn_gmapsapi" size="45" value="<?php echo get_option( 'ktn_gmapsapi' ); ?>" />
			<a href="https://code.google.com/apis/console" target="blank"><?php _e( 'Need an API key?', 'ktn' ); ?></a>
		<?php
	}

	public function ktn_instagramapi_client_id_input_callback() {
		?>
			<input type="text" name="ktn_instagramapi_client_id" id="ktn_instagramapi_client_id" size="45" value="<?php echo get_option( 'ktn_instagramapi_client_id' ); ?>" />
			<a href="http://instagram.com/developer/clients/manage/" target="_blank"><?php _e( 'Need a Client ID?', 'ktn' ); ?></a>
		<?php
	}

	public function ktn_instagramapi_client_secret_input_callback() {
		?>
			<input type="text" name="ktn_instagramapi_client_secret" id="ktn_instagramapi_client_secret" size="45" value="<?php echo get_option( 'ktn_instagramapi_client_secret' ); ?>" />
		<?php
	}

	public function ktn_instagramapi_token_input_callback() {
		$token = get_option( 'ktn_instagramapi_token' );
		$response = isset( $_GET['code'] ) ? $_GET['code'] : false;
		$saved = ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == true ) ? $_GET['settings-updated'] : false;
		$redirect_url = trailingslashit( admin_url() ) . 'edit.php?post_type=ktn_map&page=ktn_opts';
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
	public function ktn_custom_post_type() {
		register_post_type( 'ktn_map',
			array(
				'labels' => array(
					'name' => __( 'Maps' ),
					'singular_name' => __( 'Map' ),
					'menu_name'          => _x( 'Karten Maps', 'admin menu', 'ktn' ),
					'name_admin_bar'     => _x( 'Map', 'add new on admin bar', 'ktn' ),
					'add_new'            => _x( 'Add New Map', 'map', 'ktn' ),
					'add_new_item'       => __( 'Add New Map', 'ktn' ),
					'new_item'           => __( 'New Map', 'ktn' ),
					'edit_item'          => __( 'Edit Map', 'ktn' ),
					'view_item'          => __( 'View Map', 'ktn' ),
					'all_items'          => __( 'All Maps', 'ktn' ),
					'search_items'       => __( 'Search Maps', 'ktn' ),
					'parent_item_colon'  => __( 'Parent Maps:', 'ktn' ),
					'not_found'          => __( 'No maps found.', 'ktn' ),
					'not_found_in_trash' => __( 'No maps found in Trash.', 'ktn' )
				),
				'menu_icon' => 'dashicons-location-alt',
				'public' => false,
				'has_archive' => false,
				'publicly_queryable' => true,
				'show_in_nav_menus' => false,
				'show_ui' => true,
				'supports' => array(
					'title',
				)
			)
		);
	}

	/**
	 * Custom columns for map post type
	 */
	public function ktn_custom_post_type_columns( $columns ) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Map' ),
			'shortcode' => __( 'Shortcode' ),
			'id' => __( 'ID' ),
			'date' => __( 'Date' ),
		);

		return $columns;
	}

	/**
	 * Populating custom map post type columns
	 */
	public function ktn_manage_custom_post_type_columns( $column, $post_id ) {
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

	/**
	 * Post meta setup
	 */
	public function ktn_meta_setup() {
		add_action( 'add_meta_boxes', array( $this, 'ktn_add_meta_box' ) );
		add_action( 'save_post', array( $this, 'ktn_save_meta' ), 10, 2 );
	}

	/**
	 * Add post meta box
	 */
	public function ktn_add_meta_box() {
		add_meta_box(
			'ktn_meta',
			esc_html__( 'Map Settings', 'ktn' ),
			array( $this, 'ktn_meta_box_view' ),
			'ktn_map',
			'normal',
			'default'
		);
	}

	/**
	 * Post meta view
	 */
	public function ktn_meta_box_view( $object, $box ) {
		wp_nonce_field( 'ktn_save_meta', 'ktn_meta_nonce' );

		?>
		<p>
			<label class="req" for="ktn-meta-users"><?php _e( 'Users <small>(seperate by comma, must be a public user account)</small>', 'ktn' ); ?></label>
			<br />
			<input class="widefat" type="text" name="ktn-meta-users" id="ktn-meta-users" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_users', true ) ); ?>" size="30" />
		</p>
		<p>
			<label class="req" for="ktn-meta-hashtags"><?php _e( 'Hashtag <small>(don\'t include #)</small>', 'ktn' ); ?></label>
			<br />
			<input size="20" type="text" name="ktn-meta-hashtags" id="ktn-meta-hashtags" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_hashtags', true ) ); ?>" size="30" />
		</p>
		<p>
			<label class="req" for="ktn-meta-start-date"><?php _e( 'Start Date', 'ktn' ); ?></label>
			<br />
			<input size="20" type="text" name="ktn-meta-start-date" id="ktn-meta-start-date" class="ktn-datepicker" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_start_date', true ) ); ?>" size="30" />
		</p>
		<p>
			<label class="req" for="ktn-meta-end-date"><?php _e( 'End Date', 'ktn' ); ?></label>
			<br />
			<input size="20" type="text" name="ktn-meta-end-date" id="ktn-meta-end-date" class="ktn-datepicker" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ktn_meta_end_date', true ) ); ?>" size="30" />
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
	public function ktn_save_meta( $post_id, $post ) {
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
			$this::ktn_set_meta( $post_id, $field, $meta );
		}
	}

	/**
	 * Set Maps post meta
	 */
	public function ktn_set_meta( $post_id, $field, $meta ) {
		// Get the posted data and sanitize it for use as an HTML class
		$new_meta_value = ( isset( $_POST[ $field ] ) ? $_POST[ sanitize_html_class( $field ) ] : false );

		// Get the meta key
		$meta_value = get_post_meta( $post_id, $meta, true );

		// If a new meta value was added and there was no previous value, add it
		if ( $new_meta_value && '' === $meta_value ) {
			add_post_meta( $post_id, $meta, $new_meta_value, true );
		}
		// If there is no new meta value but an old value exists, delete it
		else if ( ! $new_meta_value && $meta_value ) {
			delete_post_meta( $post_id, $meta, $meta_value );
		}
		// If the new meta value does not match the old value, update it
		else if ( $new_meta_value && $new_meta_value !== $meta_value ) {
			update_post_meta( $post_id, $meta, $new_meta_value );
		}
	}

	/**
	 * Set constants right after WordPress core is loaded
	 */
	public function ktn_set_opts() {
		$gmaps = get_option( 'ktn_gmapsapi' );
		$instagram = get_option( 'ktn_instagramapi_token' );

		if ( $gmaps && $instagram ) {
			if ( ! defined( 'KARTEN_GMAPS_API_KEY' ) && ! defined( 'KARTEN_INSTAGRAM_API_KEY' ) ) {
				define( 'KARTEN_GMAPS_API_KEY', $gmaps );
				define( 'KARTEN_INSTAGRAM_API_KEY', $instagram );
			}
		}
	}

	/**
	 * Are the admin settings set?
	 */
	public function ktn_get_opts() {
		if ( defined( 'KARTEN_GMAPS_API_KEY' ) && defined( 'KARTEN_INSTAGRAM_API_KEY' ) ) {
			return array(
				'maps' => KARTEN_GMAPS_API_KEY,
				'instagram' => KARTEN_INSTAGRAM_API_KEY,
			);
		}
		
		return false;
	}
}

new KartenSetup;
