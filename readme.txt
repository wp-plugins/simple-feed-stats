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
Tested up to: 3.5
Stable tag: 20130104
Version: 20130104
License: GPLv2 or later

Simple Feed Stats makes it easy to track your feeds, add custom content, and display your feed statistics on your site.

== Description ==

[Simple Feed Stats](http://perishablepress.com/simple-feed-stats/) (SFS) tracks your feeds automatically using a variety of methods, and provides a wealth of tools and options for further configuration and management. Also displays your subscriber count via template tag or shortcode. Fully configurable. Visit the "Simple Feed Stats" settings page for stats, tools, and more info.

**Features**

* Dashboard widget - provides quick overview of your feed stats
* Custom feed content - embellish your feed with custom graphics, markup, &amp; text content
* Custom feed count - display any number or text for your feed count
* Custom CSS - use your own styles to customize your feed stats
* Shortcodes and template tags to display daily, total, RSS2, and comment stats
* Clear, reset, restore, delete - options to clear the cache, reset your stats, restore default settings, and delete the SFS database table

**Tracking methods**

Simple Feed Stats provides three four different ways to track your feeds:

* Default tracking - tracks directly via feed request
* Custom tracking - tracks via embedded post image
* Alternate tracking - tracks via embedded feed image
* Open tracking - open tracking via embedded image

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

Display feed count as plain-text number:

`[sfs_subscriber_count]`

Display feed stats with a FeedBurner-style badge:

`[sfs_count_badge]`

Display RSS2 stats in plain-text:

`[sfs_rss2_count]`

Display Comment stats in plain-text:

`[sfs_comments_count]`

See the plugin settings page for more infos.

**Template Tags**

Display daily stats as plain-text number:

`<?php if(function_exists('sfs_display_subscriber_count')) sfs_display_subscriber_count(); ?>`

Display daily stats as a nice FeedBurner-style badge:

`<?php if(function_exists('sfs_display_count_badge')) sfs_display_count_badge(); ?>`

Display total stats as plain-text:

`<?php if(function_exists('sfs_display_total_count')) sfs_display_total_count(); ?>`

See the plugin settings page for more infos.

== Upgrade Notice ==

To upgrade, simply upload the new version and you should be good to go.

== Screenshots ==

Screenshots and more info available at the [SFS Homepage](http://perishablepress.com/simple-feed-stats/).

== Changelog ==

= 20130104 =

* Implemented WP Cron to improve caching
* Updated database queries according to new protocols
* Added margins to submit buttons (now required as WP 3.5)
* Added sfs_display_total_count() template tag for "all-time" stats
* Renamed external file used for current info and news
* Added shortcode to display daily RSS2 stats: [sfs_rss2_count]
* Added shortcode to display daily Comment stats: [sfs_comments_count]
* Renamed "truncate" function to "sfs_truncate"
* Disabled tracking for RSS feeds, which auto-redirect to RSS2
* Fixed bug causing occasional display of "0" for feed count

= Previous versions =

* 20121031: Added MultiSite compatibility.
* 20121029: Renamed the wp-version check function to prefix with "sfs_". Fixed toggle panels, added easyTooltip jQuery plugin.
* 20121027: Fixed some PHP warnings and notices for undefined index and variables.
* 20121025: Added option to filter by referrer
* 20121010: Initial plugin release

== Frequently Asked Questions ==

To ask a question, visit the [SFS Homepage](http://perishablepress.com/simple-feed-stats/) or [contact me](http://perishablepress.com/contact/).

== Donations ==

I created this plugin with love for the WP community. To show support, consider purchasing my new book, [.htaccess made easy](http://htaccessbook.com/), or my WordPress book, [Digging into WordPress](http://digwp.com/).

Links, tweets and likes also appreciated. Thanks! :)
