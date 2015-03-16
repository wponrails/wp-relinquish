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

load_textdomain('wp-relinquish', __DIR__.'/languages/'.WPLANG.'.mo');

// instantiate loader and register namespaces
$loader = new \Aura\Autoload\Loader;
$loader->register();
$loader->addPrefix('Hoppinger\WordPress\Relinquish', __DIR__.'/src/');

// instantiate this plugin
$relinquish_plugin = new \Hoppinger\WordPress\Relinquish\Plugin;

// [TODO] find a place for this
function wp_relinquish_json_prepare_post($_post, $post) {
  if ( ! function_exists('get_fields')) {
    return $_post;
  }

  $_post['acf_fields'] = [];

  if ($fields = get_fields($post['ID'])) {
    $_post['acf_fields'] = $fields;
  }

  if ('page' == $post['post_type']) {
	$_post['template'] = str_replace( '.php', '', $post['page_template'] );
  }

  $seoMeta = array(
    'focuskw'              => get_post_meta($post['ID'], '_yoast_wpseo_focuskw', true),
    'title'                => get_post_meta($post['ID'], '_yoast_wpseo_title', true),
    'metadesc'             => get_post_meta($post['ID'], '_yoast_wpseo_metadesc', true),
    'linkdex'              => get_post_meta($post['ID'], '_yoast_wpseo_linkdex', true),
    'meta-robots-noindex'  => (get_post_meta($post['ID'], '_yoast_wpseo_meta-robots-noindex', true) == '1'),
    'meta-robots-nofollow' => (get_post_meta($post['ID'], '_yoast_wpseo_meta-robots-nofollow', true) == '1'),
    'meta-robots-adv'      => get_post_meta($post['ID'], '_yoast_wpseo_meta-robots-adv', true),
    'sitemap-include'      => (get_post_meta($post['ID'], '_yoast_wpseo_sitemap-include', true) == 'always'),
    'sitemap-prio'         => get_post_meta($post['ID'], '_yoast_wpseo_sitemap-prio', true),
    'canonical'            => get_post_meta($post['ID'], '_yoast_wpseo_canonical', true),
    'redirect'             => get_post_meta($post['ID'], '_yoast_wpseo_redirect', true),
    'og-title'             => get_post_meta($post['ID'], '_yoast_wpseo_opengraph-title', true),
    'og-description'       => get_post_meta($post['ID'], '_yoast_wpseo_opengraph-description', true),
    'og-image'             => get_post_meta($post['ID'], '_yoast_wpseo_opengraph-image', true),
  );

  $_post['seo_fields'] = $seoMeta;

  return $_post;
}

add_filter('json_prepare_post', 'wp_relinquish_json_prepare_post', 10, 2);


// [TODO] find a place for this
// this is a function to add WP SEO stuff of category in the JSON
function wp_relinquish_json_prepare_term($_term, $term) {
  $options = get_option('wpseo_taxonomy_meta', '');

  $seoMeta = array(
    'title'               => $options['category'][$term->term_id]['wpseo_title'], // wpseo_title
    'metadesc'            => $options['category'][$term->term_id]['wpseo_desc'], // wpseo_desc
    'canonical'           => $options['category'][$term->term_id]['wpseo_canonical'],
    'meta-robots-noindex' => $options['category'][$term->term_id]['wpseo_noindex'] == '1',
    'sitemap-include'     => $options['category'][$term->term_id]['wpseo_sitemap_include'] == 'always',
  );

  $_term['seo_fields'] = $seoMeta;

  return $_term;

}
add_filter('json_prepare_term', 'wp_relinquish_json_prepare_term', 10, 2);
