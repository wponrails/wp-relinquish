<?php
/*
Plugin Name: WP Relinquish
Version: 0.0.1-alpha
Description: With this plugin WordPress can <em>relinquish</em> content serving to an external system, for instance a Rails application with the <a href="https://github.com/hoppinger/wp-connector">wp-connector gem</a>.
Author: Hoppinger
Author URI: http://www.hoppinger.com
Plugin URI: https://github.com/hoppinger/wp-relinquish
Text Domain: wp-relinquish
Domain Path: /languages
*/

load_textdomain( 'wp-relinquish', __DIR__ . '/languages/' . WPLANG . '.mo' );

// instantiate loader and register namespaces
$loader = new \Aura\Autoload\Loader;
$loader->register();
$loader->addPrefix( 'Hoppinger\WordPress\Relinquish', __DIR__ . '/src/' );

// instantiate this plugin
$relinquish_plugin = new \Hoppinger\WordPress\Relinquish\Plugin;

// [TODO] find a place for this
function wp_relinquish_json_prepare_post( $_post, $post ) {
  if ( ! defined( 'get_fields ' ) ) {
    return;
  }

  $_post['acf_fields'] = [];

  if ( $fields = get_fields( $post['ID'] ) ) {
    $_post['acf_fields'] = $fields;
  }

  return $_post;
}
add_filter( 'json_prepare_post', 'wp_relinquish_json_prepare_post', 10, 2 );
