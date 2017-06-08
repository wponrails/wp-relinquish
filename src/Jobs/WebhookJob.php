<?php

namespace Hoppinger\WordPress\Relinquish\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class WebhookJob extends \OutofSight\Job {
  public function perform($method, $url, $params = []) {
    $client = new Client();
    $request = $client->createRequest($method, $url);
    $request_body = $request->getBody();

    if ( ! empty($params)) {
      foreach ($params as $key => $value) {
        $request_body->setField($key, $value);
      }
    }

    if (defined('WP_CONNECTOR_API_KEY')) {
      $request_body->setField('api_key', WP_CONNECTOR_API_KEY);
    }

    $client->send($request);
  }
}
