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
