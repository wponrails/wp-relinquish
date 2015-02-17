<?php

namespace Hoppinger\WordPress\Relinquish;

use GuzzleHttp\Client;

class Plugin {
  public $synched_types  = [];
  public $relinquish_url = '';

  public function __construct() {
    $this->filters();
    $this->actions();

    if ( defined( 'RELINQUISH_URL' ) ) {
      $this->relinquish_url = RELINQUISH_URL;
    }

    $this->relinquish_url = apply_filters(
      'wp_relinquish',
      $this->relinquish_url
    );

    $this->relinquish_url = trailingslashit( $this->relinquish_url );
  }

  public function filters() {
    add_filter( 'json_prepare_post', [
      $this, 'wp_api_acf_json_prepare_post'
    ], 10, 3 );
  }

  public function actions() {
    // send headers to allow the adminbar to be loaded above rails
    add_action( 'send_headers', [ $this, 'send_headers' ] );

    add_action( 'plugins_loaded', [ $this, 'synch_types' ] );

    // content editing actions
    add_action( 'insert_post', [ $this, 'save_post' ], 10, 3 );
    add_action( 'save_post', [ $this, 'save_post' ], 10, 3 );

    add_action( 'trashed_post', [ $this, 'after_trash_post' ] );
    add_action( 'delete_post', [ $this, 'before_delete_post' ] );

    // hook attachments
    add_action( 'edit_attachment', [ $this, 'save_attachment' ] );
    add_action( 'add_attachment', [ $this, 'save_attachment' ] );
  }

  public function synch_types() {
    $this->synched_types = apply_filters(
      'wp_relinquish/synch_type',
      $this->synched_types
    );
  }

  public function save_post( $post_id, $post, $updated ) {
    if ( $post->post_status == 'auto-draft' ) {
      return false;
    }

    if ( wp_is_post_revision( $post_id ) ) {
      return false;
    }

    if ( ! in_array( $post->post_type, $this->synched_types )  ) {
      return false;
    }

    if ( $post->post_status == 'draft' ) {
      return false;
    }


    $client = new Client();

    $response = $client->post( RELINQUISH_URL . "/wp-connector/{$post->post_type}/", [
      'body' => [ 'ID' => $post_id ],
      ] );

    return true;
  }

  public function save_attachment( $post_id ) {
    $post = get_post( $post_id );

    if ( $post->post_status == 'auto-draft' ) {
      return false;
    }

    if ( wp_is_post_revision( $post_id ) ) {
      return false;
    }

    if ( ! in_array( $post->post_type, $this->synched_types )  ) {
      return false;
    }

    if ( $post->post_status == 'draft' ) {
      return false;
    }

    $client = new Client();

    $url = RELINQUISH_URL . "/wp-connector/{$post->post_type}/";

    $response = $client->post( $url, [
      'body' => array( 'ID' => $post_id ),
      ] );

    return true;
  }

  public function after_trash_post( $post_id ) {

    if ( wp_is_post_revision( $post_id ) ) {
      return false;
    }

    $post = get_post( $post_id );

    if ( ! in_array( $post->post_type, $this->synched_types )  ) {
      return false;
    }

    $client  = new Client();
    $request = $client->createRequest( 'POST', RELINQUISH_URL . "/wp-connector/{$post->post_type}/" );
    $request->getBody()->setField( 'ID', $post_id );

    try {
      $response = $client->send( $request );
    } catch ( Guzzle\Http\Exception\BadResponseException $e ) {
      echo 'Uh oh! ' . $e->getMessage();
    }


    return true;
  }

  public function before_delete_post( $post_id ) {

    if ( wp_is_post_revision( $post_id ) ) {
      return false;
    }

    $post = get_post( $post_id );

    if ( ! in_array( $post->post_type, $this->synched_types )  ) {
      return false;
    }

    $client = new Client();
    $response = $client->delete( RELINQUISH_URL . "/wp-connector/{$post->post_type}/{$post_id}" );

    return true;
  }

  public function send_headers() {
    header( 'Access-Control-Allow-Origin: ' . RELINQUISH_URL );
    header( 'Access-Control-Allow-Credentials: true' );
  }


  public function wp_api_acf_json_prepare_post( $_post, $post, $context ) {

    $_post['acf_fields'] = [];

    if ( $fields = get_fields( $post['ID'] ) ) {
      $_post['acf_fields'] = $fields;
    }

    return $_post;
  }

}
