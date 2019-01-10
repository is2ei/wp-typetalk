<?php
/**
 * Main plugin's file
 * 
 * @package WP_Typetalk
 */

// Block direct access to the file via url.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin Name: WP Typetalk
 * Plugin URI: 
 * Description: This plugin allows you to send notifications to Typetalk topics when certain events in WordPress occur.
 * Version: 0.1.1
 * Author: Issei Horie
 * Author URI:
 * Text Domain: wp-typetalk
 * License: GPL v2 or later
 * Requires at least: 4.3
 * Tested up to: 5.0.2
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

require_once __DIR__ . '/includes/bootstrap.php';

// Register the autoloader.
WP_Typetalk_Autoloader::register( 'WP_Typetalk', trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/' );

// Runs this plugin after all plugins are loaded.
add_action( 'plugins_loaded', function() {
	$GLOBALS['wp_typetalk'] = new WP_Typetalk_Plugin();
	$GLOBALS['wp_typetalk']->run( __FILE__ );
});

