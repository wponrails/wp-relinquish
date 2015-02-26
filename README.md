wp-relinquish
=============

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/hoppinger/wp-relinquish/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/hoppinger/wp-relinquish/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/hoppinger/wp-relinquish/badges/build.png?b=master)](https://scrutinizer-ci.com/g/hoppinger/wp-relinquish/build-status/master)

With this plugin [WordPress](http://wordpress.org) can *relinquish* content serving to an external system, for instance a Rails application with the [wp-connector](https://github.com/hoppinger/wp-connector) gem.

This plugin provides the following functionality:

* Adds configurable webhook-notifications to WP's actions, thereby allowing the external system to be informed of content changes.
* Provides a means to serve WP's admin bar, so it may be used the website that the external system serves.

Please refer to [the README of the wp-connector gem](https://github.com/hoppinger/wp-connector/blob/master/README.md) for a detailed explaination of *why* splitting "content serving" out of WP is a good idea, and *how* this is achieved.

## Getting started

The endpoint url where all webhooks will be fired to can be set in two different ways.

Defining a constant:

```
  define( 'RELINQUISH_TO', 'http://example.com/' );
```

or add a filter:
```
  add_filter( 'wp_relinquish/relinqish_to', 'relinqish_to' );

  function relinqish_to( $url ) {
    return 'http://example.com/';
  }
```

## Contributing

Please. You know the drill: create issue, fork, resolve issue, submit pull request.

## License

(c) Copyright 2015, Hoppinger B.V.

MIT licensed, as found in the [LICENSE file](https://github.com/hoppinger/wp-relinquish/blob/master/LICENSE).
