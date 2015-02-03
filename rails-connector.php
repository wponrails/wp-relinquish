<?php
/*
Plugin Name: Rails Connector
Version: 0.1-alpha
Description: PLUGIN DESCRIPTION HERE
Author: YOUR NAME HERE
Author URI: YOUR SITE HERE
Plugin URI: PLUGIN SITE HERE
Text Domain: rails-connector
Domain Path: /languages
*/


load_textdomain( 'rails-connector', __DIR__ . '/languages/' . WPLANG . '.mo' );

define( 'RAILS_CONNECTOR_ROOT', __DIR__ );
define( 'RAILS_CONNECTOR_URL', plugin_dir_url( __FILE__ ) );

// instantiate loader and register namespaces
$loader = new \Aura\Autoload\Loader;
$loader->register();
$loader->addPrefix( 'RailsConnector', __DIR__ . '/src/' );

// instantiate this plugin
$rc = new \RailsConnector\Plugin;

// set properties for the plugin
$rc->textdomain = 'rails-connector';
