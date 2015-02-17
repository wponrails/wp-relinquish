wp-relinquish
=============

With this [WordPress](http://wordpress.org) plugin a WP site can "relinquish" content serving to an extertnal system, for instance a [Rails](http://rubyonrails.org) application with the [wp-connector gem](https://github.com/hoppinger/wp-connector).

This plugin provides the following functionality:

* Adds configurable webhook-notifications to WP's actions, thereby allowing the external system to be informed of content changes.
* Provides a means to serve WP's admin bar, so it may be used the website that the external system serves. 

Please refer to [the README of the wp-connector gem](https://github.com/hoppinger/wp-connector/blob/master/README.md) for a detailed explaination of *why* splitting "content serving" out of WP is a good idea, and *how* this is achieved.


### Contributing

Please. You know the drill: create issue, fork, resolve issue, submit pull request.


### License

(c) Copyright 2015, Hoppinger B.V.

MIT licensed, as found in the [LICENSE file](https://github.com/hoppinger/wp-relinquish/blob/master/LICENSE).
