<?php

namespace Hoppinger\WordPress\Relinquish;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Plugin {
  public $synched_types = [];
  public $relinqish_to  = null;
  public $textdomain    = 'wp-relinquish';

  private $endpoint     = null;

  public function __construct() {
    if (defined('RELINQUISH_TO')) {
      $this->relinqish_to = RELINQUISH_TO;
    }

    $this->relinqish_to = apply_filters(
      'wp_relinquish/relinqish_to',
      $this->relinqish_to
    );

    $this->relinqish_to = trailingslashit($this->relinqish_to);

    // only apply all the hooks if the endpoint url is correctly set
    if ( ! empty($this->relinqish_to)) {
      $this->actions();
      $this->filters();
    }
  }

  public function filters() {
    add_filter('json_prepare_post', [$this, 'preview_slugs'], 10, 2);
    add_filter('json_prepare_post', [$this, 'preview_published_at'], 10, 2);

    // set post links for previews
    add_filter('post_link', array($this, 'set_post_link'), 10, 2);
    add_filter('post_type_link', array($this, 'set_post_link'), 10, 2);
    add_filter('page_link', array($this, 'set_page_link'), 10, 2);

    // delay preview so the other app has time to process
    add_filter('wp_redirect', [$this,'delay_preview']);
  }

  public function actions() {
    // send headers to allow the adminbar to be loaded above rails
    add_action('send_headers', [$this, 'send_headers']);

    add_action('plugins_loaded', [$this, 'synch_post_types']);

    // content editing actions
    add_action('insert_post', [$this, 'save_post'], 10, 3);
    add_action('save_post', [$this, 'save_post'], 10, 3);
    add_action('edit_attachment', [$this, 'save_media']);

    add_action('trashed_post', [$this, 'after_trash_post']);
    add_action('deleted_post', [$this, 'after_delete_post']);

    // hook attachments
    add_action('edit_attachment', [$this, 'save_attachment']);
    add_action('add_attachment', [$this, 'save_attachment']);

    // error notices
    add_action('admin_notices', [$this, 'admin_notices']);

    // hook categories & tags
    add_action('edit_term', [$this, 'save_term'], 10, 3);
    add_action('create_term', [$this, 'save_term'], 10, 3);
    add_action('delete_term', [$this, 'delete_term'], 10, 3);

    // adminbar url fix
    add_action('wp_before_admin_bar_render', [$this, 'before_admin_bar_render']);

    // hook into redirection plugin
    add_action('redirection_redirect_after_create', [$this, 'save_redirect']);
    add_action('redirection_redirect_after_update', [$this, 'save_redirect']);
    add_action('redirection_redirect_after_delete', [$this, 'delete_redirect']);
  }

  public function before_admin_bar_render() {
    // adminbar uses a global...
    global $wp_admin_bar;

    // remove the menu item because it uses home_url() by default
    $wp_admin_bar->remove_node('view-site');

    // add the menu item again with the correct frontend url
    $wp_admin_bar->add_menu( array(
      'parent' => 'site-name',
      'id'     => 'view-site',
      'title'  => __( 'Visit Site' ),
      'href'   => RELINQUISH_FRONTEND,
    ) );

  }

  public function synch_post_types() {
    $this->synched_types = apply_filters(
      'wp_relinquish/synch_post_types',
      $this->synched_types
    );
  }

  public function save_media($post_id) {
    $post = get_post($post_id);
    return $this->save_post($post_id, $post);
  }

  public function unpublish_post($post_id, $post) {
    $this->fire_webhook('POST', $this->relinqish_to."{$post->post_type}/{$post_id}/unpublish", []);

    return true;
  }

  public function save_post($post_id, $post) {
    if ($post->post_status == 'auto-draft') {
      return false;
    }

    if ($post->post_status == 'trash') {
      return false;
    }

    if (wp_is_post_revision($post_id)) {
      return false;
    }

    if ( ! in_array($post->post_type, $this->synched_types)) {
      return false;
    }

    # Make sure to not update in Rails when a new 'draft' post is added to WP
    if ($post->post_status == 'draft' && $post->post_date == $post->post_modified) {
      return false;
    }

    $this->fire_webhook('POST', $this->relinqish_to."{$post->post_type}/", [
      'ID' => $post_id,
      'preview' => apply_filters('is_preview_post', $post),
    ]);

    return true;
  }

  public function add_notice_query_var($location) {
    remove_filter('redirect_post_location', array($this, 'add_notice_query_var'), 99);
    return add_query_arg(array('wp-relinquish-error' => urlencode($this->endpoint)), $location);
  }

  public function admin_notices() {
    if ( ! isset($_GET['wp-relinquish-error'])) {
      return;
    }

    $endpoint = urldecode($_GET['wp-relinquish-error']);

    if (current_user_can('manage_options')) {
      $notice = sprintf(__('Could not relinquish to <a href="%1$s">%2$s</a>', $this->textdomain), esc_url($endpoint), esc_url($endpoint));
    } else {
      $notice = __('Could not update cache');
    }
    // [TODO] refactor so no html is inside this class
?>
   <div class="error">
      <p><?php echo $notice ?></p>
   </div>
<?php
  }

  public function save_attachment($post_id) {
    $post = get_post($post_id);

    if ($post->post_status == 'auto-draft') {
      return false;
    }

    if (wp_is_post_revision($post_id)) {
      return false;
    }

    if ( ! in_array($post->post_type, $this->synched_types)) {
      return false;
    }

    if ($post->post_status == 'draft') {
      return false;
    }

    $this->fire_webhook('POST', $this->relinqish_to."{$post->post_type}/", [
      'ID' => $post_id,
      ]);

    return true;
  }

  public function after_trash_post($post_id) {

    if (wp_is_post_revision($post_id)) {
      return false;
    }

    $post = get_post($post_id);

    if ( ! in_array($post->post_type, $this->synched_types)) {
      return false;
    }

    $client = new Client();
    $client->delete($this->relinqish_to."{$post->post_type}/".$post_id.'?api_key='.WP_CONNECTOR_API_KEY.'&status='.$post->post_status);

    return true;
  }

  public function after_delete_post($post_id) {

    if (wp_is_post_revision($post_id)) {
      return false;
    }

    $post = get_post($post_id);

    if ( ! in_array($post->post_type, $this->synched_types)) {
      return false;
    }

    $client = new Client();
    // NOTE: The post_status for permanently deleted posts is blank
    $client->delete($this->relinqish_to."{$post->post_type}/".$post_id.'?api_key='.WP_CONNECTOR_API_KEY.'&status=delete');

    return true;
  }

  public function save_term($term_id, $tt_id, $taxonomy) {
    $taxonomy = $this->standardize_taxonomy_name($taxonomy);

    $this->fire_webhook('POST', $this->relinqish_to."{$taxonomy}/", [
      'ID' => $term_id,
      ]);

    return true;
  }

  public function delete_term($term_id, $tt_id, $taxonomy) {
    $taxonomy = $this->standardize_taxonomy_name($taxonomy);

    $client = new Client();
    $client->delete($this->relinqish_to."{$taxonomy}/".$term_id.'?api_key='.WP_CONNECTOR_API_KEY);

    return true;
  }

  public function save_redirect($id) {
    return $this->fire_webhook('POST', $this->relinqish_to."redirect/", ['ID' => $id]);
  }

  public function delete_redirect($id) {
    $client = new Client();
    return $client->delete($this->relinqish_to."redirect/{$id}?api_key=".WP_CONNECTOR_API_KEY);
  }

  /**
   * @param string $method
   * @param string $endpoint
   */
  private function fire_webhook($method, $endpoint, $body = null) {
    // set this for the query var to keep the endpoint across redirects
    $this->endpoint = $endpoint;

    // create a guzzle client
    $client = new Client();

    // create the request base on the method and endpoint url
    $request = $client->createRequest($method, $endpoint);

    $request_body = $request->getBody();

    // add body fields if needed
    if ( ! empty($body)) {
      foreach ($body as $key => $value) {
        $request_body->setField($key, $value);
      }
    }

    // add api key to all the requests
    if (defined('WP_CONNECTOR_API_KEY')) {
      $request_body->setField('api_key', WP_CONNECTOR_API_KEY);
    }

    // run the request and handle exceptions
    try {
      $client->send($request);
    } catch (RequestException $e) {
      // add filter to transport this error across the redirect
      add_filter('redirect_post_location', array($this, 'add_notice_query_var'));
    }

    return true;
  }

  public function set_page_link($url, $page_id) {
    $post = get_post($page_id);
    return $this->set_post_link($url, $post);
  }

  public function set_post_link($url, $post) {
        // handle draft posts preview with token
    if (in_array($post->post_status, ['draft', 'pending'])) {
      // when a post is first saved as a preview the post_name is not set...
      $slug = $post->post_name;
      if (empty($post->post_name)) {
        $slug = sanitize_title($post->post_title);
        $url = $url.$slug;
      }

      $hash = hash('sha256', WP_CONNECTOR_SECRET.$slug);
      $url = add_query_arg('token', $hash, $url);
    }

    return $url;
  }

  public function send_headers() {
    $domain = untrailingslashit(RELINQUISH_FRONTEND);
    header('Access-Control-Allow-Origin: '.$domain);
    header('Access-Control-Allow-Credentials: true');
  }

  public function preview_slugs($_post, $post) {
    // when a post is saved as a draft no slug is set
    // this fixes this for the API so the external system gets a slug
    if (empty($_post['slug'])) {
      $_post['slug'] = sanitize_title($_post['title']);
    }
    return $_post;
  }

  public function preview_published_at($_post) {
    // when a post is saved as a draft no slug is set
    // this fixes this for the API so the external system gets a slug
    if (empty($_post['date'])) {
      $_post['date'] = $_post['modified'];
    }

    return $_post;
  }

  /**
   * Delay preview redirects to give external app time to process
   */
  public function delay_preview($location) {
    if ( ! defined('RELINQUISH_PREVIEW_DELAY') ) {
      return $location;
    }

    if (strpos('preview=true', $location) !== 0) {
      // sleeptime in seconds
      sleep(RELINQUISH_PREVIEW_DELAY);
    }

    return $location;
  }

  private function standardize_taxonomy_name($taxonomy) {
    // make post_tag consistent with other taxonomies
    if ($taxonomy == 'post_tag') {
      $taxonomy = 'tag';
    }
    return $taxonomy;
  }
}
