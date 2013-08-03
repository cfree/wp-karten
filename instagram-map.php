<?php
/*
Plugin Name: Instagram Map
Description: Display Instagram photos on a map based on a specific hashtag found in specific users' feeds
Version: 0.5
Author: Craig Freeman
Author URI: http://www.craigfreeman.net
*/

/*  Copyright 2012 Craig Freeman  (email : craigfreeman@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the tecfs of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Google Maps API Key
define("KEY", "AIzaSyD-BbP0VKgvXUE408A1ErOUOLXXpaaKYHw");

// Enqueue scripts/styles in template header
function cf_scripts() {
	if(is_category('denver')) {
		// Add appropriate code to header
		wp_register_script( 'cf_location_google_maps', 'https://maps.googleapis.com/maps/api/js?key='.KEY.'&sensor=false', null, null, false);
		wp_enqueue_script( 'cf_location_google_maps' );
	
		wp_register_script( 'cf_location_script', plugins_url('/scripts.js', __FILE__), array('jquery', 'cf_location_google_maps'), null, false);
		wp_enqueue_script( 'cf_location_script' );
	
		// Stylesheets	
		wp_register_style( 'cf_location_style', plugins_url('/style.css', __FILE__) );
		wp_enqueue_style( 'cf_location_style' );
	}
}

add_action('wp_enqueue_scripts', 'cf_scripts');

function showMap() {
	echo file_get_contents(dirname( __FILE__ ) . '/map_template.php'); 
}