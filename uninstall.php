<?php // uninstall remove options

if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) exit();

// delete options
delete_option('sfs_options');

// delete transients
delete_transient('sfs_cron_cache');

// delete custom tables
global $wpdb;
$table_name = $wpdb->prefix . 'simple_feed_stats';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");




