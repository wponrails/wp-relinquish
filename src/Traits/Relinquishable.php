<?php

namespace Hoppinger\WordPress\Relinquish\Traits;

use WP_JSON_Response,
    WP_JSON_Server;

trait Relinquishable {

  public function register_routes( $routes ) {
    $routes = parent::register_routes( $routes );

    $route = $this->base . '/preview/(?P<id>\d+)';
    $routes[ $route ] = array(
      array( array( $this, 'get_preview' ), WP_JSON_Server::READABLE ),
    );

    return $routes;
  }

  /**
   * Retrieve a preview.
   *
   * @uses get_preview()
   * @param int $id Post ID
   * @param array $fields Post fields to return (optional)
   * @return array Post entity
   */
  public function get_preview( $id, $context = 'view' ) {
    $id = (int) $id;

    if ( empty( $id ) ) {
      return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );
    }

    $post = get_post( $id, ARRAY_A );

    if ( empty( $post['ID'] ) ) {
      return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );
    }

    // [TODO] turn of permisions for now... auth later in a different way?
    // if ( ! $this->check_read_permission( $post ) ) {
    //   return new WP_Error( 'json_user_cannot_read', __( 'Sorry, you cannot read this post.' ), array( 'status' => 401 ) );
    // }

    $response = new WP_JSON_Response();
    $response->header( 'Last-Modified', mysql2date( 'D, d M Y H:i:s', $post['post_modified_gmt'] ) . 'GMT' );

    // down the line of classes more checks are done if the post it accesible or not
    // by setting the status to publish one of these checks return true and the post will be rendered
    $real_status         = $post['post_status'];
    $post['post_status'] = 'publish';

    $post = $this->prepare_post( $post, $context );

    // set the status back to the real status
    $post['status'] = $real_status;

    if ( is_wp_error( $post ) ) {
      return $post;
    }

    foreach ( $post['meta']['links'] as $rel => $url ) {
      $response->link_header( $rel, $url );
    }

    $response->link_header( 'alternate',  get_permalink( $id ), array( 'type' => 'text/html' ) );
    $response->set_data( $post );

    return $response;
  }

}