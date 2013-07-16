=== Simple Feed Stats ===

Plugin Name: Simple Feed Stats
Plugin URI: http://perishablepress.com/simple-feed-stats/
Description: Tracks your feeds, adds custom content, and displays your feed statistics on your site.
Tags: feed, feeds, stats, statistics, feedburner, tracking, subscribers
Author URI: http://monzilla.biz/
Author: Jeff Starr
Contributors: specialk
Donate link: http://m0n.co/donate
Requires at least: 3.4
Tested up to: 3.5
Version: 20130715
Stable tag: trunk
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

**Testing**

To verify that the plugin is working properly, do the following:

1. Visit the "Your Info / More Info" panel in the plugin's settings
2. Click on each of "Your feed URLs" and refresh the settings page
3. In the "Tools and Options" panel, click "clear cache"
4. Refresh the settings page

After performing these steps, your "Current Feed Stats" and "Total Feed Stats" should both display some numbers, based on the feed URLs that you clicked in step 2. This means that the plugin is working using its default settings. Similar testing should be done for other feed-tracking options. Note that not all tracking methods (or browsers/devices) work for all types of feeds; for example, the "Alt Tracking" method is required to record hits for RDF feeds. 

**Notes**

To update your feed stats at any time (without waiting for the automatic 12-hour interval), click the "clear cache" link in the "Tools and Options" settings panel.

See the plugin settings page for more infos.

== Upgrade Notice ==

To upgrade, simply upload the new version and you should be good to go.

== Screenshots ==

Screenshots and more info available at the [SFS Homepage](http://perishablepress.com/simple-feed-stats/).

== Changelog ==

= 20130715 =

* Improved localization support
* Resolved numerous PHP Warnings
* Replaced deprecated WP functions
* Added additional info to readme.txt
* Removed filter_cron_schedules()
* Added cleanup of scheduled chron jobs upon deactivation
* Tightened security of tracker file
* Added default timezone (UTC)
* Overview and Updates admin panels toggled open by default
* General code check n clean

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

I created this plugin with love for the WP community. To show support, consider purchasing one of my books: [The Tao of WordPress](http://wp-tao.com/), [Digging into WordPress](http://digwp.com/), or [.htaccess made easy](http://htaccessbook.com/).

Links, tweets and likes also appreciated. Thanks! :)
