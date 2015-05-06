wp-relinquish
=============

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/hoppinger/wp-relinquish/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/hoppinger/wp-relinquish/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/hoppinger/wp-relinquish/badges/build.png?b=master)](https://scrutinizer-ci.com/g/hoppinger/wp-relinquish/build-status/master)

This [WordPress](http://wordpress.org) plugin is part of the [**WP on Rails**](https://github.com/wponrails) project, which limits a WP's responsibilities to managing content while entrusting a Rails application with serving public request and providing a basis for customizations.

The `wp-relinquish` plugin, as the name implies, allows [WordPress](http://wordpress.org) to *relinquish* content serving to an external system, in case of the **WP on Rails** architecture that is a Rails application with [wp-connector](https://github.com/wponrails/wp-connector) installed.

The following plugins are needed or recommended when using `wp-relinquish` to set up a **WP on Rails** architecture:

* **json-rest-api** ([site](http://wp-api.org), [plugin page](https://wordpress.org/plugins/json-rest-api), [repo](https://github.com/WP-API/WP-API)) [mandatory] — WP plugin that adds a modern RESTful web-API to a WordPress site. This module will be shipped as part of WordPress Core in the future.
* **json-rest-api-menu-routes** ([plugin page](https://wordpress.org/plugins/wp-api-menus), [repo](https://github.com/nekojira/wp-api-menus)) [optional] — WordPress plugin that extends the WP API with functionality regard WP's menus. This is only needed if you want to manage menus from WP and display them from Rails.
* **wp-relinquish-theme** ([repo](https://github.com/wponrails/wp-relinquish-theme)) [optional] — This WP theme is to be used together with the `wp-relinquish` plugin, it displays only the admin bar which (which can then be picked up when previewing content that is to be served from Rails).

This plugin provides the following functionality:

* It makes it very easy setup [webhook](http://en.wikipedia.org/wiki/Webhook)-notifications for WP actions, thereby allowing the Rails applications to be informed of content changes (addition/modification/removal).
* Provides a means to serve WP's admin bar, so it may be embedded by the Rails application. This to ensure a fully functioning the admin bar when previewing content is available to administrators.

This plugin does not transfer content into the external system, it only helps sending notifications as webhook calls.

Please refer to [the README of the wp-connector gem](https://github.com/wponrails/wp-connector/blob/master/README.md) for a detailed explaination of *why* moving "content serving" out of WP is a good idea and *how* this is achieved.


## Getting started

The end-point url, where all webhook calls will be made to, can be set in two different ways.

By (1) defining a constant:

```php
define( 'RELINQUISH_TO', 'http://example.com/wp-webhook-endpoint' );
```

Or by (2) adding a filter:

```php
add_filter( 'wp_relinquish/relinqish_to', 'relinqish_to' );

function relinqish_to( $url ) {
  return 'http://example.com/wp-webhook-endpoint';
}
```


## Contributing

Contributions are most welcome! You know the drill: create issue, fork, resolve issue, submit pull request.


## License

(c) Copyright 2015, Hoppinger B.V.

MIT licensed, as found in the [LICENSE file](https://github.com/wponrails/wp-relinquish/blob/master/LICENSE).
