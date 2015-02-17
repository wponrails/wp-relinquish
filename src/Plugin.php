<?php

namespace Hoppinger\WordPress\Relinquish;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Plugin {
  public $synched_types = [];
  public $relinqish_to  = null;
  public $textdomain    = 'wp-relinquish';

  private $error        = null;
  private $endpoint     = null;

  public function __construct() {
    if ( defined( 'RELINQUISH_TO' ) ) {
      $this->relinqish_to = RELINQUISH_TO;
    }

    $this->relinqish_to = apply_filters(
      'wp_relinquish/relinqish_to',
      $this->relinqish_to
    );

    $this->relinqish_to = trailingslashit( $this->relinqish_to );

    // only apply all the hooks if the endpoint url is correctly set
    if ( ! empty( $this->relinqish_to ) ) {
      $this->filters();
      $this->actions();
    }
  }

  public function filters() {
    if ( defined( 'get_fields' ) ) {
      add_filter( 'json_prepare_post', [
        $this, 'wp_api_acf_json_prepare_post'
        ], 10, 3 );
    }
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

    // error notices
    add_action( 'admin_notices', [ $this, 'admin_notices' ] );
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
    $this->endpoint = $this->relinqish_to . "{$post->post_type}/";

    try {
      $client->post( $this->endpoint, [
        'body' => [ 'ID' => $post_id ],
        ] );
    } catch ( RequestException $e ) {
      // add filter to transport this error across the redirect
      add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ) );
    }

    return true;
  }

  public function add_notice_query_var( $location ) {
    remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
    return add_query_arg( array( 'wp-relinquish-error' => urlencode( $this->endpoint ) ), $location );
  }

  public function admin_notices() {
    if ( ! isset( $_GET['wp-relinquish-error'] ) ) {
      return;
    }

    $endpoint = urldecode( $_GET['wp-relinquish-error'] );

    if ( current_user_can( 'manage_options' ) ) {
      $notice = sprintf( __( 'Could not relinquish to <a href="%1$s">%2$s</a>', $this->textdomain ), esc_url( $endpoint ), esc_url( $endpoint ) );
    } else {
      $notice = __( 'Could not update cache' );
    }
    // [TODO] refactor so no html is inside this class
?>
   <div class="error">
      <p><?php print $notice ?></p>
   </div>
<?php
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

    $this->endpoint = $this->relinqish_to . "{$post->post_type}/";

    try {
      $client->post( $url, [
        'body' => array( 'ID' => $post_id ),
        ] );
    } catch ( RequestException $e ) {
      // add filter to transport this error across the redirect
      add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ) );
    }

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
    $this->endpoint = $this->relinqish_to . "{$post->post_type}/";
    $request = $client->createRequest( 'POST', $this->endpoint );
    $request->getBody()->setField( 'ID', $post_id );

    try {
      $client->send( $request );
    } catch ( RequestException $e ) {
      // add filter to transport this error across the redirect
      add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ) );
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

    $this->endpoint = $this->relinqish_to . "/{$post->post_type}/{$post_id}";

    try {
      $client->delete( $this->endpoint );
    } catch ( RequestException $e ) {
      // add filter to transport this error across the redirect
      add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ) );
    }

    return true;
  }

  public function send_headers() {
    header( 'Access-Control-Allow-Origin: ' . $this->relinqish_to );
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
