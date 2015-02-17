<?php
/*
Plugin Name: WP Relinquish
Version: 0.0.1-alpha
Description:
With this WordPress plugin a WP site can "relinquish" content  serving to an
extertnal system, for instance a Rails application with the wp-connector gem.
Author: Hoppinger
Author URI: http://www.hoppinger.com
Plugin URI: https://github.com/hoppinger/wp-relinquish
Text Domain: wp-relinquish
Domain Path: /languages
*/

load_textdomain( 'wp-relinquis', __DIR__ . '/languages/' . WPLANG . '.mo' );

// instantiate loader and register namespaces
$loader = new \Aura\Autoload\Loader;
$loader->register();
$loader->addPrefix( 'Hoppinger\WordPress\Relinquish', __DIR__ . '/src/' );

// instantiate this plugin
$relinquish = new \Hoppinger\WordPress\Relinquish\Plugin;

// set properties for the plugin
$relinquish->textdomain = 'wp-relinquis';
