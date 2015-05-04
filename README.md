wp-relinquish
=============

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/hoppinger/wp-relinquish/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/hoppinger/wp-relinquish/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/hoppinger/wp-relinquish/badges/build.png?b=master)](https://scrutinizer-ci.com/g/hoppinger/wp-relinquish/build-status/master)

This plugin helps [WordPress](http://wordpress.org) to *relinquish* content serving to an external system, for instance a Rails application with [wp-connector](https://github.com/hoppinger/wp-connector) installed.

This plugin provides the following functionality:

* It makes it very easy setup [webhook](http://en.wikipedia.org/wiki/Webhook)-notifications for WP actions, thereby allowing the external system to be informed of content changes (addition/modification/removal).
* Provides a means to serve WP's admin bar, so it may be embedded by the external system that WP *reliquishes* content serving to. This to ensure a fully functioning the admin bar is still available to admins.

This plugin does not transfer content into the external system, we recommend to use the [WP REAST API](http://wp-api.org/) for that. Please refer to [the README of the wp-connector gem](https://github.com/hoppinger/wp-connector/blob/master/README.md) for a detailed explaination of *why* moving "content serving" out of WP is a good idea and *how* this is achieved.


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

MIT licensed, as found in the [LICENSE file](https://github.com/hoppinger/wp-relinquish/blob/master/LICENSE).
