<?php
/*
Plugin Name: Mapstagram
Description: Display Instagram photos on a Google Maps map
Version: 0.5
Author: Craig Freeman
Author URI: http://www.craigfreeman.net
*/

// This file is part of the Mapstagram plugin for WordPress
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
	echo file_get_contents(dirname( __FILE__ ) . '/map-template.php'); 
}