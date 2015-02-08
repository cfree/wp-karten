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
Author URI: http://craigfreeman.net
License: GPL 2
*/

// This file is part of the Karten plugin for WordPress
//
// Copyright (c) 2012-2015 Craig Freeman. All rights reserved.
// http://craigfreeman.net
//
// Released under the GPL 2 license
// http://www.gnu.org/licenses/gpl-2.0.html
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
 * Load classes
 */ 
include plugin_dir_path( __FILE__ ) . 'classes/karten-setup.php';
include plugin_dir_path( __FILE__ ) . 'classes/karten-main.php';

/**
 * @DONE: Implement shortcode
 * @DONE: Implement template tag
 * @DONE: Enqueue scripts only when needed
 *    - Comb post for short code pre-save, add meta array of associated Map post IDs?
 *    - Check meta for map post IDs when loading page, create URLs and localize, enqueue scripts/styles?
 *    - Enqueue scripts at time of shortcode processing
 * @DONE: Tie short code to scripts
 * @DONE: Make it easier to get API settings
 * @DONE: Reformat JS
 	* @DONE: Use OOJS for multiple maps on 1 page
 	* @DONE: Construct URLs
 * @DONE: Get Instagram user IDs
 * @DONE: Determine which could be private vs public variable, update
 * @TO-DO: Bind proper scope where necessary, remove 'scope' variable
 *
 * @TO-DO: Object orientify
 * @DONE: Fix Start Addr, End Addr, Max number of posts not saving
 * @DONE: Move settings page to inside Maps post type
 *
 * @DONE: Decide on license: GPL
 * @DONE: Update README (how to get Google Maps API, how to create Instagram client & how to get Instagram API access token, explain cache)
 *
 * @TO-DO: Code review
 */
