=== Simple Feed Stats ===

Plugin Name: Simple Feed Stats
Plugin URI: http://perishablepress.com/simple-feed-stats/
Description: Tracks your feeds, adds custom content, and displays your feed statistics on your site.
Tags: feed, feeds, stats, statistics, feedburner, tracking, subscribers
Author URI: http://monzilla.biz/
Author: Jeff Starr
Contributors: specialk
Donate link: http://digwp.com/
Requires at least: 3.4
Tested up to: 3.4.2
Stable tag: 20121031
Version: 20121031
License: GPLv2 or later

Simple Feed Stats makes it easy to track your feeds, add custom content, and display your feed statistics on your site.

== Description ==

Simple Feed Stats (SFS) tracks your feeds automatically using a variety of methods, and provides a wealth of tools and options for further configuration and management. Also displays your subscriber count via template tag or shortcode. Fully configurable. Visit the "Simple Feed Stats" settings page for stats, tools, and more info.

**Features**

* Dashboard widget Ð provides quick overview of your feed stats
* Custom feed content Ð embellish your feed with custom graphics, markup, &amp; text content
* Custom feed count Ð display any number or text for your feed count
* Custom CSS Ð use your own styles to customize your feed stats
* Clear, reset, restore, delete Ð options to clear the cache, reset your stats, restore default settings, and delete the SFS database table

**Tracking methods**

Simple Feed Stats provides three four different ways to track your feeds:

* Default tracking Ð tracks directly via feed request
* Custom tracking Ð tracks via embedded post image
* Alternate tracking Ð tracks via embedded feed image
* Open tracking Ð open tracking via embedded image

**Collected data**

Simple Feed Stats tracks the following data for each feed request:

* Feed type
* IP address
* Referrer
* Requested URL
* User-agent
* Date and more

== Installation ==

Upload the `/simple-feed-stats/` directory to your `/plugins/` folder and activate in the WP Admin. Then visit the Simple Feed Stats Settings page to view your stats, customize options, grab shortcodes, and more. Everything works automatically out of the box, with plenty of tools and options to customize and manage your feed stats.

**Shortcodes**

`[sfs_subscriber_count]` - displays feed count as plain-text number

`[sfs_count_badge]` - displays feed stats with a FeedBurner-style badge

See the plugin settings page for more infos.

**Template Tags**

Display stats as plain-text number:

`<?php if(function_exists('sfs_display_subscriber_count')) sfs_display_subscriber_count(); ?>`

Display stats as a nice FeedBurner-style badge:

`<?php if(function_exists('sfs_display_count_badge')) sfs_display_count_badge(); ?>`

See the plugin settings page for more infos.

== Upgrade Notice ==

To upgrade, simply upload the new version and you should be good to go.

== Screenshots ==

Screenshots and more info available at the [SFS Homepage](http://perishablepress.com/simple-feed-stats/).

== Changelog ==

20121031: Added MultiSite compatibility.
20121029: Renamed the wp-version check function to prefix with "sfs_". Fixed toggle panels, added easyTooltip jQuery plugin.
20121027: Fixed some PHP warnings and notices for undefined index and variables.
20121025: Added option to filter by referrer
20121010: Initial plugin release

== Frequently Asked Questions ==

To ask a question, visit the [SFS Homepage](http://perishablepress.com/simple-feed-stats/) or [contact me](http://perishablepress.com/contact/).

== Donations ==

I created this plugin with love for the WP community. To show support, consider purchasing my new book, [.htaccess made easy](http://htaccessbook.com/), or my WordPress book, [Digging into WordPress](http://digwp.com/).

Links, tweets and likes also appreciated. Thanks! :)
