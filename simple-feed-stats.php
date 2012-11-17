<?php
/*
	Plugin Name: Simple Feed Stats
	Plugin URI: http://perishablepress.com/simple-feed-stats/
	Description: Tracks feeds, displays subscriber counts, custom feed content, and much more.
	Author: Jeff Starr
	Author URI: http://monzilla.biz/
	Version: 20121031
	Usage: Visit the "Simple Feed Stats" settings page for stats, tools, and more info.
	License: GPL v2
*/

// set version
$sfs_version = '20121031';

// get options
$options = get_option('sfs_options');

// cache-busting
function sfs_randomizer() {
	$sfs_randomizer = rand(1000000,9999999);
	return $sfs_randomizer;
}
$sfs_rand = sfs_randomizer();
global $sfs_rand;

// require minimum version of WordPress
add_action('admin_init', 'sfs_require_wp_version');
function sfs_require_wp_version() {
	global $wp_version;
	$plugin = plugin_basename(__FILE__);
	$plugin_data = get_plugin_data(__FILE__, false);

	if (version_compare($wp_version, "3.4", "<")) {
		if (is_plugin_active($plugin)) {
			deactivate_plugins($plugin);
			$msg =  '<p><strong>' . $plugin_data['Name'] . '</strong> requires WordPress 3.4 or higher, and has been deactivated!</p>';
			$msg .= '<p>Please upgrade WordPress and try again.</p><p>Return to the <a href="' .admin_url() . '">WordPress Admin area</a>.</p>';
			wp_die($msg);
		}
	}
}

// create stats table
add_action('init', 'sfs_create_table');
function sfs_create_table() {
	global $wpdb;
	// $wpdb->show_errors();
	$table = $wpdb->prefix . 'simple_feed_stats';

	if ($wpdb->get_var("show tables like '$table'") !== $table) {
		$sql =  "CREATE TABLE " . $table . " (
			`id` mediumint(10) unsigned NOT NULL AUTO_INCREMENT,
			`logtime`  varchar(200) NOT NULL default '',
			`request`  varchar(200) NOT NULL default '',
			`referer`  varchar(200) NOT NULL default '',
			`type`     varchar(200) NOT NULL default '',
			`qstring`  varchar(200) NOT NULL default '',
			`address`  varchar(200) NOT NULL default '',
			`tracking` varchar(200) NOT NULL default '',
			`agent`    varchar(200) NOT NULL default '',
			PRIMARY KEY (`id`),
			cur_timestamp TIMESTAMP(8)
		);";
	}
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	if (isset($sql)) dbDelta($sql);

	if (!isset($wpdb->stats)) {
		$wpdb->stats = $table; 
		$wpdb->tables[] = str_replace($wpdb->prefix, '', $table); 
	}
}

// simple feed tracking (default tracking)
// tracks every type of feed request
add_action('init', 'simple_feed_stats');
function simple_feed_stats() {

	$options = get_option('sfs_options');
	if ($options['sfs_tracking_method'] == 'sfs_default_tracking') {
		global $wpdb;
		$logtime = mysql_real_escape_string(date("F jS Y, h:ia", time() - 25200)); // 25200 seconds = -7 hours
		$request = mysql_real_escape_string('http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		$referer = mysql_real_escape_string($_SERVER['HTTP_REFERER']);
		$qstring = mysql_real_escape_string($_SERVER['QUERY_STRING']);
		$address = mysql_real_escape_string($_SERVER['REMOTE_ADDR']);
		$agent   = mysql_real_escape_string($_SERVER['HTTP_USER_AGENT']);

		$feed_rdf  = get_bloginfo('rdf_url'); // RDF feed and RDF comments (=bug)
		$feed_rss  = get_bloginfo('rss_url'); // RSS2 feed
		$feed_rss2 = get_bloginfo('rss2_url'); // RSS feed
		$feed_atom = get_bloginfo('atom_url'); // Atom feed
		$feed_coms = get_bloginfo('comments_rss2_url'); // used for RSS and RSS2 comments
		$feed_coms_atom = get_bloginfo('comments_atom_url'); // used for Atom comments
		$feed_coms_rdf = get_bloginfo('comments_rss2_url') . 'rdf/'; // used for RDF comments (see $feed_rdf)

		$wp_feeds = array($feed_rdf, $feed_rss, $feed_rss2, $feed_atom, $feed_coms, $feed_coms_atom, $feed_coms_rdf);

		if ($request == $feed_rdf) {
			$type = 'RDF';
		} elseif ($request == $feed_rss) {
			$type = 'RSS';
		} elseif ($request == $feed_rss2) {
			$type = 'RSS2';
		} elseif ($request == $feed_atom) {
			$type = 'Atom';
		} elseif ($request == $feed_coms) {
			$type = 'Comments';
		} elseif ($request == $feed_coms_atom) {
			$type = 'Comments';
		} elseif ($request == $feed_coms_rdf) {
			$type = 'Comments';
		} else {
			$type = 'other';
		}

		if (!$referer) $referer = 'blank';
		$tracking = 'default';

		if (in_array($request, $wp_feeds)) {
			$table = $wpdb->prefix . 'simple_feed_stats';
			$query  = "INSERT INTO $table (logtime,   request,   referer,   type,   qstring,   address,   tracking,   agent) ";
			$query .= "VALUES           ('$logtime','$request','$referer','$type','$qstring','$address','$tracking','$agent')";
			$result = @mysql_query($query);
		}
	}
}

// custom tracking (via post image)
function sfs_feed_tracking($content) {
	global $wp_query;
	global $sfs_rand;
	if (is_feed()) {
		$feed_type = get_query_var('feed');
		if ($wp_query->current_post == 0) {
			return '<img src="' . plugins_url() . '/simple-feed-stats/tracker.php?sfs_tracking=true&amp;feed_type=' . $feed_type . '&amp;v=' . $sfs_rand . '" width="1" height="1" alt=""> ' . $content;
		} else {
			return $content;
		}
	} else {
		return $content;
	}
}
if ($options['sfs_tracking_method'] == 'sfs_custom_tracking') {

	if (isset($_SERVER['HTTP_USER_AGENT'])){ $user_agent = $_SERVER['HTTP_USER_AGENT']; }
	if (strlen(strstr($user_agent, "Firefox")) > 0) { 
		// track feeds in Firefox
		add_action('rss_head','sfs_alt_tracking_rss');
		add_action('rss2_head','sfs_alt_tracking_rss');
		add_action('commentsrss2_head','sfs_alt_tracking_comments');
		add_action('comments_atom_head','sfs_alt_tracking_comments');
	}
	add_filter('the_content', 'sfs_feed_tracking');
	add_filter('the_excerpt', 'sfs_feed_tracking');
}

// alt tracking method
function sfs_alt_tracking_rss() {
	global $sfs_rand; ?>

	<image>
		<title><?php bloginfo_rss('name'); ?></title>
		<url><?php echo plugins_url(); ?>/simple-feed-stats/tracker.php?sfs_tracking=true&amp;feed_type=feed&amp;v=<?php echo $sfs_rand; ?></url>
		<link><?php bloginfo_rss('url'); ?></link>
		<width>1</width><height>1</height>
		<description><?php bloginfo('description'); ?></description>
	</image>
<?php }

function sfs_alt_tracking_comments() {
	global $sfs_rand; ?>

	<image>
		<title><?php bloginfo_rss('name'); ?></title>
		<url><?php echo plugins_url(); ?>/simple-feed-stats/tracker.php?sfs_tracking=true&amp;feed_type=comments&amp;v=<?php echo $sfs_rand; ?></url>
		<link><?php bloginfo_rss('url'); ?></link>
		<width>1</width><height>1</height>
		<description><?php bloginfo('description'); ?></description>
	</image>
<?php }

function sfs_alt_tracking_rdf() {
	global $sfs_rand; ?>

	<image rdf:about="<?php echo plugins_url(); ?>/simple-feed-stats/tracker.php?sfs_tracking=true&amp;feed_type=rdf&amp;v=<?php echo $sfs_rand; ?>">
		<title><?php bloginfo_rss('name'); ?></title>
		<url><?php echo plugins_url(); ?>/simple-feed-stats/tracker.php?sfs_tracking=true&amp;feed_type=rdf&amp;v=<?php echo $sfs_rand; ?></url>
		<link><?php bloginfo_rss('url'); ?></link>
		<description><?php bloginfo('description'); ?></description>
	</image>
<?php }

function sfs_alt_tracking_atom() {
	global $sfs_rand; ?>

	<feed>
		<icon><?php echo plugins_url(); ?>/simple-feed-stats/tracker.php?sfs_tracking=true&amp;feed_type=atom&amp;v=<?php echo $sfs_rand; ?></icon>
	</feed>
<?php }

if ($options['sfs_tracking_method'] == 'sfs_alt_tracking') {

	// RSS  @ http://backend.userland.com/rss091
	// RSS2 @ http://cyber.law.harvard.edu/rss/rss.html
	add_action('rss_head','sfs_alt_tracking_rss');
	add_action('rss2_head','sfs_alt_tracking_rss');
	add_action('commentsrss2_head','sfs_alt_tracking_comments');

	// RDF @ http://web.resource.org/rss/1.0/spec
	add_action('rdf_header','sfs_alt_tracking_rdf');

	// Atom @ http://www.atomenabled.org/developers/syndication/atom-format-spec.php
	add_action('atom_head','sfs_alt_tracking_atom');
	add_action('comments_atom_head','sfs_alt_tracking_comments');
}

// display settings link on plugin page
add_filter ('plugin_action_links', 'sfs_plugin_action_links', 10, 2);
function sfs_plugin_action_links($links, $file) {
	if ($file == plugin_basename(__FILE__)) {
		$sfs_links = '<a href="'. get_admin_url() .'options-general.php?page=simple-feed-stats/simple-feed-stats.php">'. __('Settings') .'</a>';
		array_unshift($links, $sfs_links);
	}
	return $links;
}

// delete plugin settings
function sfs_delete_options_on_deactivation() {
	delete_option('sfs_options');
}
if ($options['default_options'] == 1) {
	register_uninstall_hook (__FILE__, 'sfs_delete_options_on_deactivation');
}

// delete stats table
function sfs_delete_table_on_deactivation() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'simple_feed_stats';
	$sql = "DROP TABLE " . $table_name;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	// dbDelta($sql);
	$result = @mysql_query($sql);
}
if ($options['sfs_delete_table'] == 1) {
	register_deactivation_hook(__FILE__, 'sfs_delete_table_on_deactivation');
}

// define default settings
register_activation_hook (__FILE__, 'sfs_add_defaults');
function sfs_add_defaults() {
	$tmp = get_option('sfs_options');
	if(($tmp['default_options'] == '1') || (!is_array($tmp))) {
		$arr = array(
			'sfs_custom' => '0',
			'sfs_custom_enable' => 0,
			'sfs_custom_styles' => '.sfs-subscriber-count { width: 88px; overflow: hidden; height: 26px; color: #424242; font: 9px Verdana, Geneva, sans-serif; letter-spacing: 1px; }
.sfs-count { width: 86px; height: 17px; line-height: 17px; margin: 0 auto; background: #ccc; border: 1px solid #909090; border-top-color: #fff; border-left-color: #fff; }
.sfs-count span { display: inline-block; height: 11px; line-height: 12px; margin: 2px 1px 2px 2px; padding: 0 2px 0 3px; background: #e4e4e4; border: 1px solid #a2a2a2; border-bottom-color: #fff; border-right-color: #fff; }
.sfs-stats { font-size: 6px; line-height: 6px; margin: 1px 0 0 1px; word-spacing: 2px; text-align: center; text-transform: uppercase; }',

			'sfs_number_results' => '3',
			'sfs_tracking_method' => 'sfs_default_tracking',
			'sfs_open_image_url' => plugins_url() . '/simple-feed-stats/testing.gif',
			'sfs_delete_table' => 0,
			'default_options' => 0,
			'sfs_feed_content_before' => '',
			'sfs_feed_content_after' => '',
		);
		update_option('sfs_options', $arr);
	}
}

// define style options
$sfs_tracking_method = array(
	'sfs_disable_tracking' => array(
		'value' => 'sfs_disable_tracking',
		'label' => __('<strong>Disable tracking</strong> (disables all tracking)') . '<span class="tooltip" title="' . __('Note: no stats or data will be deleted.') . '">?</span>'
	),
	'sfs_default_tracking' => array(
		'value' => 'sfs_default_tracking',
		'label' => __('<strong>Default tracking</strong> (tracks via feed requests)') . '<span class="tooltip" title="' . __('Recommended if serving your own feeds.') . '">?</span>'
	),
	'sfs_custom_tracking' => array(
		'value' => 'sfs_custom_tracking',
		'label' => __('<strong>Custom tracking</strong> (tracks via embedded post image)') . '<span class="tooltip" title="' . __('Recommended if redirecting your feed to FeedBurner (using Full-text feeds only; use &ldquo;Open Tracking&rdquo; for FeedBurner Summary feeds).') . '">?</span>'
	),
	'sfs_alt_tracking' => array(
		'value' => 'sfs_alt_tracking',
		'label' => __('<strong>Alternate tracking</strong> (tracks via embedded feed image)') . '<span class="tooltip" title="' . __('Experimental tracking method.') . '">?</span>'
	),
	'sfs_open_tracking' => array(
		'value' => 'sfs_open_tracking',
		'label' => __('<strong>Open tracking</strong> (open tracking via image)') . '<span class="tooltip" title="' . __('Track any feed or web page by using the open-tracking URL as the <code>src</code> for any <code>img</code> tag. Tip: this is a good alternate method of tracking your FeedBurner feeds.') . '">?</span>'
	),
);

// sanitize and validate input
function sfs_validate_options($input) {
	global $sfs_tracking_method;

	if (!isset($input['sfs_custom_enable'])) $input['sfs_custom_enable'] = null;
	$input['sfs_custom_enable'] = ($input['sfs_custom_enable'] == 1 ? 1 : 0);

	if (!isset($input['sfs_delete_table'])) $input['sfs_delete_table'] = null;
	$input['sfs_delete_table'] = ($input['sfs_delete_table'] == 1 ? 1 : 0);

	if (!isset($input['default_options'])) $input['default_options'] = null;
	$input['default_options'] = ($input['default_options'] == 1 ? 1 : 0);

	if (!isset($input['sfs_tracking_method'])) $input['sfs_tracking_method'] = null;
	if (!array_key_exists($input['sfs_tracking_method'], $sfs_tracking_method)) $input['sfs_tracking_method'] = null;

	$input['sfs_custom'] = wp_filter_nohtml_kses($input['sfs_custom']);
	$input['sfs_custom_styles'] = wp_filter_nohtml_kses($input['sfs_custom_styles']);
	$input['sfs_number_results'] = wp_filter_nohtml_kses($input['sfs_number_results']);
	$input['sfs_open_image_url'] = wp_filter_nohtml_kses($input['sfs_open_image_url']);

	$input['sfs_feed_content_before'] = wp_kses_post($input['sfs_feed_content_before']);
	$input['sfs_feed_content_after'] = wp_kses_post($input['sfs_feed_content_after']);

	return $input;
}

// whitelist settings
add_action('admin_init', 'sfs_init');
function sfs_init() {
	register_setting('sfs_plugin_options', 'sfs_options', 'sfs_validate_options');
}

// add the options page
add_action ('admin_menu', 'sfs_add_options_page');
function sfs_add_options_page() {
	add_options_page('Simple Feed Stats', 'Simple Feed Stats', 'manage_options', __FILE__, 'sfs_render_form');
}

// add query-string variable @ http://www.addedbytes.com/code/querystring-functions/
function add_querystring_var($url, $key, $value) { 
	$url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&'); 
	$url = substr($url, 0, -1);
	if (strpos($url, '?') === false) { 
		return ($url . '?' . $key . '=' . $value); 
	} else { 
		return ($url . '&' . $key . '=' . $value); 
	}
}

// truncate() by David Duong: shorten string & add ellipsis 
function truncate($string, $max = 50, $rep = '') {
    $leave = $max - strlen ($rep);
    return substr_replace($string, $rep, $leave);
}

// display stats template tag
function sfs_display_subscriber_count() {
	$options = get_option('sfs_options'); 
	if ($options['sfs_custom_enable'] == 1) {
		echo $options['sfs_custom'];
	} else {
		if (is_multisite()) {
			$feed_count = get_site_transient('feed_count');
		} else {
			$feed_count = get_transient('feed_count');	
		}
		if ($feed_count) {
			echo $feed_count;
		} else {
			echo '0';	
		}
	}
}

// display stats shortcode
add_shortcode('sfs_subscriber_count','sfs_subscriber_count');
function sfs_subscriber_count() { 
	$options = get_option('sfs_options'); 
	if ($options['sfs_custom_enable'] == 1) {
		return $options['sfs_custom'];
	} else {
		if (is_multisite()) {
			$feed_count = get_site_transient('feed_count');
		} else {
			$feed_count = get_transient('feed_count');	
		}
		if ($feed_count) {
			return $feed_count;
		} else {
			return '0';	
		}
	}
}

// feed count badge template tag
function sfs_display_count_badge() {
	$options = get_option('sfs_options'); 
	$sfs_pre_badge = '<div class="sfs-subscriber-count"><div class="sfs-count"><span>';
	$sfs_post_badge = '</span> readers</div><div class="sfs-stats">Simple Feed Stats</div></div>';

	if ($options['sfs_custom_enable'] == 1) {
		echo $sfs_pre_badge . $options['sfs_custom'] . $sfs_post_badge;
	} else {
		if (is_multisite()) {
			$feed_count = get_site_transient('feed_count');
		} else {
			$feed_count = get_transient('feed_count');	
		}
		if ($feed_count) {
			echo $sfs_pre_badge . $feed_count . $sfs_post_badge;
		} else {
			echo $sfs_pre_badge . '0' . $sfs_post_badge;
		}
	}
}

// feed count badge shortcode
add_shortcode('sfs_count_badge','sfs_count_badge');
function sfs_count_badge() {
	$options = get_option('sfs_options'); 
	$sfs_pre_badge = '<div class="sfs-subscriber-count"><div class="sfs-count"><span>';
	$sfs_post_badge = '</span> readers</div><div class="sfs-stats">Simple Feed Stats</div></div>';

	if ($options['sfs_custom_enable'] == 1) {
		return $sfs_pre_badge . $options['sfs_custom'] . $sfs_post_badge;
	} else {
		if (is_multisite()) {
			$feed_count = get_site_transient('feed_count');
		} else {
			$feed_count = get_transient('feed_count');	
		}
		if ($feed_count) {
			return $sfs_pre_badge . $feed_count . $sfs_post_badge;
		} else {
			return $sfs_pre_badge . '0' . $sfs_post_badge;	
		}
	}
}

// conditional css inclusion
function sfs_include_badge_styles() {
	$options = get_option('sfs_options');
	$sfs_badge_styles = esc_textarea($options['sfs_custom_styles']);
	echo '<style type="text/css">' . "\n";
	echo $sfs_badge_styles . "\n";
	echo '</style>' . "\n";
}
if ($options['sfs_custom_styles'] !== '') {
	add_action('wp_head', 'sfs_include_badge_styles');
}

// custom footer content
function sfs_feed_content($content) {
	global $wp_query;
	$options = get_option('sfs_options');
	$custom_before = $options['sfs_feed_content_before'];
	$custom_after  = $options['sfs_feed_content_after'];
	if (is_feed()) {
		return $custom_before . $content . $custom_after;
	} else {
		return $content;
	}
}
if (($options['sfs_feed_content_before'] !== '') || ($options['sfs_feed_content_after'] !== '')) {
	add_filter('the_content', 'sfs_feed_content');
	add_filter('the_excerpt', 'sfs_feed_content');
}

// sfs dashboard widget 
function sfs_dashboard_widget() { 
	$sfs_query_current = sfs_query_database('current_stats'); ?>

	<style type="text/css">
		.sfs_table { border-collapse: collapse; }
		.sfs_table th { font-size: 12px; }
		.sfs_table td { padding: 5px 10px; color: #555; font: 12px Helvetica, sans-serif; border: 1px solid #dfdfdf; }
		.sfs_table td.sfs-type { display: table-cell; vertical-align: middle; text-shadow: 1px 1px 1px #fff; font: bold 16px/16px Georgia, serif; }
		.sfs_table td.RDF      { background-color: #fce8fc; }
		.sfs_table td.RSS      { background-color: #d9e8f9; }
		.sfs_table td.RSS2     { background-color: #d5f2d5; }
		.sfs_table td.Atom     { background-color: #fafac0; }
		.sfs_table td.Comments { background-color: #fee6cc; }
		.sfs_table td.custom   { background-color: #ffe3e3; }
		.sfs_table td.other    { background-color: #e5e5e5; }
	</style>
	<p><?php _e('Current Subscriber Count'); ?>: <strong><?php sfs_display_subscriber_count(); ?></strong></p>
	<div class="sfs_table-wrap">
		<table class="widefat sfs_table">
			<thead>
				<tr>
					<th><?php _e('RDF'); ?></th>
					<th><?php _e('RSS'); ?></th>
					<th><?php _e('RSS2'); ?></th>
					<th><?php _e('Atom'); ?></th>
					<th><?php _e('Comments'); ?></th>
					<th><?php _e('Custom'); ?></th>
					<th><?php _e('Other'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="sfs-type RDF"><?php echo $sfs_query_current[0]; ?></td>
					<td class="sfs-type RSS"><?php echo $sfs_query_current[1]; ?></td>
					<td class="sfs-type RSS2"><?php echo $sfs_query_current[2]; ?></td>
					<td class="sfs-type Atom"><?php echo $sfs_query_current[3]; ?></td>
					<td class="sfs-type Comments"><?php echo $sfs_query_current[4]; ?></td>
					<td class="sfs-type custom"><?php echo $sfs_query_current[5]; ?></td>
					<td class="sfs-type other"><?php echo $sfs_query_current[6]; ?></td>
				</tr>
			</tbody>
		</table>
	</div>
	<p><a href="<?php get_admin_url(); ?>options-general.php?page=simple-feed-stats/simple-feed-stats.php"><?php _e('More info, tools, and options'); ?></a></p>

<?php }
function add_custom_dashboard_widget() {
	wp_add_dashboard_widget('sfs_dashboard_widget', 'Simple Feed Stats', 'sfs_dashboard_widget');
}
add_action('wp_dashboard_setup', 'add_custom_dashboard_widget');

// query database for stats
function sfs_query_database($sfs_query_type) {
	global $wpdb;

	if ($sfs_query_type == 'current_stats') {

		$count_recent_rdf = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE type='RDF' AND cur_timestamp BETWEEN TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY)) AND NOW()");
		$count_recent_rdf = mysql_fetch_row($count_recent_rdf);
		$count_recent_rdf = $count_recent_rdf[0];
	
		$count_recent_rss = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE type='RSS' AND cur_timestamp BETWEEN TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY)) AND NOW()");
		$count_recent_rss = mysql_fetch_row($count_recent_rss);
		$count_recent_rss = $count_recent_rss[0];
	
		$count_recent_rss2 = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE type='RSS2' AND cur_timestamp BETWEEN TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY)) AND NOW()");
		$count_recent_rss2 = mysql_fetch_row($count_recent_rss2);
		$count_recent_rss2 = $count_recent_rss2[0];
	
		$count_recent_atom = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE type='Atom' AND cur_timestamp BETWEEN TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY)) AND NOW()");
		$count_recent_atom = mysql_fetch_row($count_recent_atom);
		$count_recent_atom = $count_recent_atom[0];
	
		$count_recent_comments = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE type='Comments' AND cur_timestamp BETWEEN TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY)) AND NOW()");
		$count_recent_comments = mysql_fetch_row($count_recent_comments);
		$count_recent_comments = $count_recent_comments[0];

		$count_recent_open = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE tracking='open' AND cur_timestamp BETWEEN TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY)) AND NOW()");
		$count_recent_open = mysql_fetch_row($count_recent_open);
		$count_recent_open = $count_recent_open[0];
	
		$count_recent_other = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE type='other' AND cur_timestamp BETWEEN TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY)) AND NOW()");
		$count_recent_other = mysql_fetch_row($count_recent_other);
		$count_recent_other = $count_recent_other[0];

		$sfs_query_current = array($count_recent_rdf, $count_recent_rss, $count_recent_rss2, $count_recent_atom, $count_recent_comments, $count_recent_open, $count_recent_other);
		return $sfs_query_current;
	}
	if ($sfs_query_type == 'alltime_stats') {

		$count_rdf = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE type='RDF'");
		$count_rdf = mysql_fetch_row($count_rdf);
		$count_rdf = $count_rdf[0];

		$count_rss = mysql_query("SELECT COUNT(*) FROM "  .$wpdb->prefix."simple_feed_stats WHERE type='RSS'");
		$count_rss = mysql_fetch_row($count_rss);
		$count_rss = $count_rss[0];

		$count_rss2 = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE type='RSS2'");
		$count_rss2 = mysql_fetch_row($count_rss2);
		$count_rss2 = $count_rss2[0];

		$count_atom = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE type='Atom'");
		$count_atom = mysql_fetch_row($count_atom);
		$count_atom = $count_atom[0];

		$count_comments = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE type='Comments'");
		$count_comments = mysql_fetch_row($count_comments);
		$count_comments = $count_comments[0];

		$count_open = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE tracking='open'");
		$count_open = mysql_fetch_row($count_open);
		$count_open = $count_open[0];

		$count_other = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE type='other'");
		$count_other = mysql_fetch_row($count_other);
		$count_other = $count_other[0];

		$sfs_query_alltime = array($count_rdf, $count_rss, $count_rss2, $count_atom, $count_comments, $count_open, $count_other);
		return $sfs_query_alltime;
	}
}

// create the options page
function sfs_render_form() {

	global $wpdb, $sfs_tracking_method;
	$options = get_option('sfs_options');
	$sfs_query_current = sfs_query_database('current_stats'); 
	$sfs_query_alltime = sfs_query_database('alltime_stats'); 

	if (isset($_GET["filter"])) $filter = $_GET["filter"];
	$numresults = $options['sfs_number_results'];
	
	if (isset($_GET["p"])) {
		$pagevar = (is_numeric($_GET["p"]) ? $_GET["p"] : 1);
	} else {
		$pagevar = '1';	
	}
	$offset = ($pagevar-1)*$numresults; // offset

	$numrows = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats");
	$numrows = mysql_fetch_row($numrows);
	$numrows = $numrows[0];
	$maxpage = ceil($numrows/$numresults);
	$i = 1;

	if (isset($_GET["reset"])) {
		if ($_GET["reset"] == "true") {
			$truncate = mysql_query("TRUNCATE " . $wpdb->prefix."simple_feed_stats");
		}
	}
	if (isset($filter)) {
		$sql = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."simple_feed_stats ORDER BY $filter ASC LIMIT $offset, $numresults"));
	} else {
		$sql = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."simple_feed_stats ORDER BY id DESC LIMIT $offset, $numresults"));
	}

	// daily count
	//$current_date = date("Y-m-d H:i:s"); // date("Y-m-d")
	$current_stats = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats WHERE cur_timestamp BETWEEN TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY)) AND NOW()"); // TYPE != 'Comments'
	$current_stats = mysql_fetch_row($current_stats);
	$current_stats = $current_stats[0];

	// all-time count
	$all_stats = mysql_query("SELECT COUNT(*) FROM " . $wpdb->prefix."simple_feed_stats");
	$all_stats = mysql_fetch_row($all_stats);
	$all_stats = $all_stats[0];

	// cache feed count
	if (is_multisite()) {
		set_site_transient('feed_count', $current_stats, 60*60*24); // 12 hour cache 60*60*12 , 24 hour cache = 60*60*24
		$feed_count = get_site_transient('feed_count');
		set_site_transient('all_count', $all_stats, 60*60*24); // 12 hour cache 60*60*12 , 24 hour cache = 60*60*24
		$all_count = get_site_transient('all_count');
	} else {
		set_transient('feed_count', $current_stats, 60*60*24); // 12 hour cache 60*60*12 , 24 hour cache = 60*60*24
		$feed_count = get_transient('feed_count');
		set_transient('all_count', $all_stats, 60*60*24); // 12 hour cache 60*60*12 , 24 hour cache = 60*60*24
		$all_count = get_transient('all_count');
	}

	// clear cache
	if (isset($_GET["cache"])) {
		if ($_GET["cache"] == "clear") {
			if (is_multisite()) {
				delete_site_transient('feed_count');
				delete_site_transient('all_count');
			} else {
				delete_transient('feed_count');
				delete_transient('all_count');
			}
		} 
	} ?>

	<style type="text/css">
		#sfs-admin {}
			#sfs-admin abbr { cursor: help; border-bottom: 1px dotted #dfdfdf; }
			#sfs-admin h2 small { font-size: 60%; }
			#sfs-admin h3 { cursor: pointer; }
			#sfs-admin h4, #sfs-admin p { margin: 15px; line-height: 18px; }
			#sfs-toggle-panels { margin: 5px 0; }
			#sfs-credit-info { margin-top: -5px; }
			#setting-error-settings_updated { margin: 10px 0 5px 0; }
			#setting-error-settings_updated p { margin: 5px; }
			.sfs-image { float: left; padding: 3px; margin-right: 15px; border: 1px solid #ccc; background-color: #fff; }
		.sfs_table-wrap { margin: 15px; }
			.sfs_table { border-collapse: collapse; }
				.sfs_table td { padding: 5px 10px; color: #555; font: 12px Helvetica, sans-serif; border: 1px solid #dfdfdf; }
					.sfs_table td.sfs-type { display: table-cell; vertical-align: middle; text-shadow: 1px 1px 1px #fff; font: bold 20px/20px Georgia, serif; }

					.sfs_table td.RDF      { background-color: #fce8fc; }
					.sfs_table td.RSS      { background-color: #d9e8f9; }
					.sfs_table td.RSS2     { background-color: #d5f2d5; }
					.sfs_table td.Atom     { background-color: #fafac0; }
					.sfs_table td.Comments { background-color: #fee6cc; }
					.sfs_table td.custom   { background-color: #ffe3e3; }
					.sfs_table td.other    { background-color: #e5e5e5; }

					#sfs_stats td.sfs-type { text-align: center; }
					#sfs_stats td.sfs-meta, 
					#sfs_stats td.sfs-details { font-size: 11px; color: #777; background-color: #fff; }
					#sfs_stats div { margin: 5px; }
					#sfs_stats div.sfs-stats-type { font-size: 12px; font-weight: bold; color: #555; }
					#sfs_stats div.sfs-stats-type span { color: #bbb; font-weight: normal; font-size: 10px; }

					#sfs-nav { float: left; width: 500px; margin: 0 15px 15px 15px; }
					#sfs-paging-wrap { float: left; width: 220px; }
					#sfs-paging { display: inline; }
					#sfs-paging-menu { width: 100px; }
					#sfs-filter-form { float: left; width: 240px; }
					.sfs-nav.top { clear: both; }

			.sfs_info td { padding: 5px 10px; }

		.button-primary { margin: 15px; }
		.sfs-list { margin: 15px 15px 25px 45px; }
		.sfs-list li { margin: 10px 0; list-style-type: disc; }
		#sfs-current { width: 100%; height: 250px; overflow: hidden; }
		#sfs-current iframe { width: 100%; height: 100%; overflow: hidden; margin: 0; padding: 0; }
		.nudge { position: relative; top: -5px; }
		.sfs-ot-img { display: block; margin-top: 10px; }
		.tooltip { 
			cursor: help; display: inline-block; width: 18px; height: 18px; text-align: center; font: bold 12px/18px Georgia, serif;
			border: 2px solid #fff; color: #fff; background-color: #359fce; -webkit-border-radius: 18px; -moz-border-radius: 18px; border-radius: 18px;
			-webkit-box-shadow: 0 0 1px rgba(0,0,0,0.3); -moz-box-shadow: 0 0 1px rgba(0,0,0,0.3); box-shadow: 0 0 1px rgba(0,0,0,0.3); 
			}
		#easyTooltip { 
			max-width: 310px; padding: 10px 15px; border: 1px solid #96c2d5; background-color: #fdfdfd;
			-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; 
			-webkit-box-shadow: 7px 7px 7px -1px rgba(0,0,0,0.3); -moz-box-shadow: 7px 7px 7px -1px rgba(0,0,0,0.3); box-shadow: 7px 7px 7px -1px rgba(0,0,0,0.3); 
			}
		/* count badge */
		.sfs-subscriber-count-wrap { margin: -5px 0 20px 20px; }
		<?php // $sfs_badge_styles = esc_textarea($options['sfs_custom_styles']); echo $sfs_badge_styles; ?>
		.sfs-subscriber-count { width: 88px; overflow: hidden; height: 26px; color: #424242; font: 9px Verdana, Geneva, sans-serif; letter-spacing: 1px; }
		.sfs-count { width: 86px; height: 17px; line-height: 17px; margin: 0 auto; background: #ccc; border: 1px solid #909090; border-top-color: #fff; border-left-color: #fff; }
		.sfs-count span { display: inline-block; height: 11px; line-height: 12px; margin: 2px 1px 2px 2px; padding: 0 2px 0 3px; background: #e4e4e4; border: 1px solid #a2a2a2; border-bottom-color: #fff; border-right-color: #fff; }
		.sfs-stats { font-size: 6px; line-height: 6px; margin: 1px 0 0 1px; word-spacing: 2px; text-align: center; text-transform: uppercase; }
	</style>

	<div id="sfs-admin" class="wrap">

		<?php screen_icon(); ?>
		<h2><?php _e('Simple Feed Stats'); ?> <small><?php global $sfs_version; echo 'v' . $sfs_version; ?></small></h2>
		<div id="sfs-toggle-panels"><a href="<?php get_admin_url() . 'options-general.php?page=simple-feed-stats/simple-feed-stats.php'; ?>"><?php _e('Toggle all panels'); ?></a></div>

		<?php // success messages
		if(isset($_GET["cache"])) { ?>
			<div id="setting-error-settings_updated" class="updated settings-error"><p><strong><?php _e('Cache cleared'); ?>.</strong></p></div>
		<?php }
		if(isset($_GET["reset"])) { ?>
			<div id="setting-error-settings_updated" class="updated settings-error"><p><strong><?php _e('All feed stats deleted'); ?>.</strong></p></div>
		<?php } ?>

		<div class="metabox-holder">	
			<div class="meta-box-sortables ui-sortable">
				<div id="sfs-overview" class="postbox">
					<h3><?php _e('Overview'); ?></h3>
					<div class="toggle default-hidden">
						<p>
							<img class="sfs-image" src="<?php echo plugins_url(); ?>/simple-feed-stats/sfs-logo.png" width="120" height="55" alt="[ Simple Feed Stats ]">
							<?php _e('Simple Feed Stats (SFS) makes it easy to track your feeds and display a subscriber count on your website.'); ?> 
							<?php _e('It also enables you to add custom content to the header and footer of each feed item.'); ?> 
							<?php _e('SFS tracks your feeds <em>automatically</em> and displays the statistics on <em>this</em> page.'); ?> 
							<?php _e('To display your subscriber count on the front-end of your site, visit'); ?> <a id="sfs-shortcodes-link" href="#sfs-shortcodes"><?php _e('Template Tags &amp; Shortcodes'); ?></a>. 
							<?php _e('To customize and manage SFS, visit'); ?> <a id="sfs-options-link" href="#sfs_custom-options"><?php _e('Tools &amp; Options'); ?></a>. 
							<?php _e('Visit the SFS Widget in the'); ?> <a href="<?php echo get_admin_url(); ?>"><?php _e('Dashboard'); ?></a> <?php _e('any time for a quick overview.'); ?> 
							<?php _e('For more information check the <code>readme.txt</code> and'); ?> <a href="http://perishablepress.com/simple-feed-stats/"><?php _e('Simple Feed Stats Homepage'); ?></a>.
						</p>
					</div>
				</div>

				<?php if ($maxpage != 0) { // begin section ?>

				<div id="sfs-count-current" class="postbox">
					<h3><?php _e('Current Subscriber Count'); ?>: <?php sfs_display_subscriber_count(); ?></h3>
					<div class="toggle default-hidden">
						<p><?php _e('Current subscribers by type (previous 24 hours)'); ?>:</p>
						<div class="sfs_table-wrap">
							<table class="widefat sfs_table">
								<thead>
									<tr>
										<th><?php _e('RDF'); ?></th>
										<th><?php _e('RSS'); ?></th>
										<th><?php _e('RSS2'); ?></th>
										<th><?php _e('Atom'); ?></th>
										<th><?php _e('Comments'); ?></th>
										<th><?php _e('Custom'); ?></th>
										<th><?php _e('Other'); ?></th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td class="sfs-type RDF"><?php echo $sfs_query_current[0]; ?></td>
										<td class="sfs-type RSS"><?php echo $sfs_query_current[1]; ?></td>
										<td class="sfs-type RSS2"><?php echo $sfs_query_current[2]; ?></td>
										<td class="sfs-type Atom"><?php echo $sfs_query_current[3]; ?></td>
										<td class="sfs-type Comments"><?php echo $sfs_query_current[4]; ?></td>
										<td class="sfs-type custom"><?php echo $sfs_query_current[5]; ?></td>
										<td class="sfs-type other"><?php echo $sfs_query_current[6]; ?></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<div id="sfs-feed-stats" class="postbox">
					<h3><?php _e('Feed Statistics'); ?></h3>
					<div class="toggle<?php if (!$filter && !$_GET["p"]) { echo ' default-hidden'; } ?>">
	
						<?php if(isset($_GET["filter"])) { 
							echo '<p>'. __('Feed stats filtered by') . ' <strong>' . $filter . '</strong> 
								[ <a href="' . get_admin_url() . 'options-general.php?page=simple-feed-stats/simple-feed-stats.php">' . __('reset') . '</a> ]</p>'; 
						} ?>
	
						<p><?php _e('Displaying page'); ?> <?php echo $pagevar; ?> <?php _e('of'); ?> <?php echo $maxpage; ?></p>
						<div id="sfs-nav">
							<div id="sfs-paging-wrap">
								<span><?php _e('Jump to page'); ?>: </span> 
								<form name="sfs-paging" id="sfs-paging">
									<select name="sfs-paging-menu" id="sfs-paging-menu" onchange="myF('parent',this,0)">
										<?php // paging
											while($i <= $maxpage) {
												$url = get_admin_url() . 'options-general.php' . add_querystring_var("?".$_SERVER["QUERY_STRING"], "p", $i);
												if($pagevar == $i) {
													echo '<option selected class="current" value="selected">'.$i.'</option>';
												} else {
													echo '<option value="'.$url.'">'.$i.'</option>';
												}
												$i++;
											} 
										?>
									</select>
								</form>
							</div>
							<div id="sfs-filter-form">
								<form action="<?php get_admin_url(); ?>options-general.php?page=simple-feed-stats/simple-feed-stats.php" method="GET">
									<input type="hidden" name="page" value="simple-feed-stats/simple-feed-stats.php" />
									<label for="filter"><?php _e('Filter data by'); ?>:</label>
									<select name="filter">
										<option value="" selected="selected">---[<?php _e('filter'); ?>]---</option>
										<option value="logtime"><?php _e('Log Time'); ?></option>
										<option value="type"><?php _e('Feed Type'); ?></option>
										<option value="address"><?php _e('IP Address'); ?></option>
										<option value="agent"><?php _e('User Agent'); ?></option>
										<option value="tracking"><?php _e('Tracking'); ?></option>
										<option value="referer"><?php _e('Referrer'); ?></option>
									</select>
									<input class="button-secondary" type="submit" />
								</form>
							</div>
						</div>
						<p class="sfs-nav top">
							<?php // nav
								if($pagevar != 1) {
									$url = get_admin_url() . 'options-general.php' . add_querystring_var("?".$_SERVER["QUERY_STRING"], "p", $pagevar-1);
									echo '<a href="' . $url . '">&laquo; ' . __('Previous results') . '</a> &bull; ';
								}
								if($pagevar != $maxpage) {
									$url = get_admin_url() . 'options-general.php' . add_querystring_var("?".$_SERVER["QUERY_STRING"], "p", $pagevar+1);
									echo '<a href="' . $url . '">' . __('Next results') . ' &raquo;</a>';
								}
							?>
						</p>
						<div class="sfs_table-wrap">
							<table id="sfs_stats" class="widefat sfs_table">
								<thead>
									<tr>
										<th><?php _e('ID'); ?></th>
										<th><?php _e('Meta'); ?></th>
										<th><?php _e('Details'); ?></th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<th><?php _e('ID'); ?></th>
										<th><?php _e('Meta'); ?></th>
										<th><?php _e('Details'); ?></th>
									</tr>
								</tfoot>
								<tbody>
									<?php foreach($sql as $s) { ?>
									<tr>
										<td class="sfs-type <?php echo $s->type; ?>"><?php echo $s->id; ?></td>
										<td class="sfs-meta">
											<div class="sfs-stats-type"><?php echo $s->type; ?> <span>/ <?php echo $s->tracking; ?></span></div>
											<div class="sfs-stats-ip"><strong><?php _e('IP'); ?>:</strong> <?php echo $s->address; ?></div>
											<div class="sfs-stats-time"><?php echo $s->logtime; ?></div>
										</td>
										<td class="sfs-details">
											<div class="sfs-stats-referrer"><strong><?php _e('Referrer'); ?>:</strong> <?php echo $s->referer; ?></div>
											<div class="sfs-stats-request"><strong><?php _e('Request'); ?>:</strong> <?php echo $s->request; ?></div>
											<div class="sfs-stats-agent"><strong><?php _e('User Agent'); ?>:</strong> <?php echo truncate($s->agent, 175, ''); ?></div>
										</td>
									</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
						<p class="sfs-nav bot">
							<?php // nav
								if($pagevar != 1) {
									$url = get_admin_url() . 'options-general.php' . add_querystring_var("?".$_SERVER["QUERY_STRING"], "p", $pagevar-1);
									echo '<a href="' . $url . '">&laquo; ' . __('Previous results') . '</a> &bull; ';
								}
								if($pagevar != $maxpage) {
									$url = get_admin_url() . 'options-general.php' . add_querystring_var("?".$_SERVER["QUERY_STRING"], "p", $pagevar+1);
									echo '<a href="' . $url . '">' . __('Next results') . ' &raquo;</a>';
								}
							?>
						</p>
					</div>
				</div>

				<?php } // end section ?>

				<div id="sfs-count-total" class="postbox">
					<h3><?php _e('Total Subscriber Count'); ?>: <?php echo $all_count; ?></h3>
					<div class="toggle default-hidden">
						<p><?php _e('All-time number of subscribers by type (everything in the database)'); ?>:</p>
						<div class="sfs_table-wrap">
							<table class="widefat sfs_table">
								<thead>
									<tr>
										<th><?php _e('RDF'); ?></th>
										<th><?php _e('RSS'); ?></th>
										<th><?php _e('RSS2'); ?></th>
										<th><?php _e('Atom'); ?></th>
										<th><?php _e('Comments'); ?></th>
										<th><?php _e('Custom'); ?></th>
										<th><?php _e('Other'); ?></th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td class="sfs-type RDF"><?php echo $sfs_query_alltime[0]; ?></td>
										<td class="sfs-type RSS"><?php echo $sfs_query_alltime[1]; ?></td>
										<td class="sfs-type RSS2"><?php echo $sfs_query_alltime[2]; ?></td>
										<td class="sfs-type Atom"><?php echo $sfs_query_alltime[3]; ?></td>
										<td class="sfs-type Comments"><?php echo $sfs_query_alltime[4]; ?></td>
										<td class="sfs-type custom"><?php echo $sfs_query_alltime[5]; ?></td>
										<td class="sfs-type other"><?php echo $sfs_query_alltime[6]; ?></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<div id="sfs_custom-options" class="postbox">
					<h3><?php _e('Tools &amp; Options'); ?></h3>
					<div class="toggle<?php if (!isset($_GET["cache"]) && !isset($_GET["reset"]) && !isset($_GET["settings-updated"])) { echo ' default-hidden'; } ?>">
						<form method="post" action="options.php">
							<?php settings_fields('sfs_plugin_options'); ?>
							<?php $options = get_option('sfs_options'); ?>

							<h4><?php _e('Tracking Method'); ?></h4>
							<div class="sfs_table-wrap">
								<table class="form-table">
									<tr>
										<th scope="row"><label class="description" for="sfs_options[sfs_tracking_method]"><?php _e('Tracking method'); ?></label></th>
										<td>
											<?php if (!isset($checked)) $checked = '';
												foreach ($sfs_tracking_method as $option) {
													$radio_setting = $options['sfs_tracking_method'];
													if ('' != $radio_setting) {
														if ($options['sfs_tracking_method'] == $option['value']) {
															$checked = "checked=\"checked\"";
														} else {
															$checked = '';
														}
													} ?>
													<input type="radio" name="sfs_options[sfs_tracking_method]" class="sfs-<?php if ($option['value'] == 'sfs_open_tracking') { echo 'open-'; } ?>tracking" value="<?php esc_attr_e($option['value']); ?>" <?php echo $checked; ?> /> <?php echo $option['label']; ?>
													<br />
											<?php } ?>
										</td>
									</tr>
									<tr class="sfs-open-tracking-url<?php if ($options['sfs_tracking_method'] !== 'sfs_open_tracking') { echo ' default-hidden'; } ?>">
										<th scope="row"><label class="description"><?php _e('Open Tracking URL'); ?></label></th>
										<td>
											<div>
												<?php _e('For use with the &ldquo;Open Tracking&rdquo; method. Use this tracking URL as the <code>src</code> for any <code>img</code>.'); ?> 
												<span class="tooltip" title="<?php _e('Tip: SFS Open Tracking is another way to track your FeedBurner feeds. Visit <code>m0n.co/a</code> for details (or google &ldquo;SFS Open Tracking&rdquo;).'); ?>">?</span>
												<br />
												<code><?php echo plugins_url(); ?>/simple-feed-stats/tracker.php?sfs_tracking=true&sfs_type=open</code>
											</div>
											<div>
												<?php _e('Example code:'); ?><br />
												<code>&lt;img src="<?php echo plugins_url(); ?>/simple-feed-stats/tracker.php?sfs_tracking=true&sfs_type=open" alt="" /&gt;</code>
											</div>
										</td>
									</tr>
									<tr class="sfs-open-tracking-image<?php if ($options['sfs_tracking_method'] !== 'sfs_open_tracking') { echo ' default-hidden'; } ?>">
										<th scope="row"><label class="description" for="sfs_options[sfs_open_image_url]"><?php _e('Open Tracking Image'); ?></label></th>
										<td>
											<div>
												<?php _e('For use with the &ldquo;Open Tracking&rdquo; method. Here you may specify the URL for open tracking.'); ?> 
												<span class="tooltip" title="<?php _e('Tip: this is the URL of the image that will be returned as the <code>src</code> for the open-tracking image. Use text/numbers only, no markup.'); ?>">?</span>
												<br />
												<input type="text" size="40" maxlength="200" name="sfs_options[sfs_open_image_url]" value="<?php echo $options['sfs_open_image_url']; ?>" />
											</div>
											<div>
												<?php _e('Current image being used for Open Tracking:'); ?><br />
												<img class="sfs-ot-img" src="<?php echo $options['sfs_open_image_url']; ?>" alt="" />
											</div>
										</td>
									</tr>
								</table>
							</div>
							<h4><?php _e('Custom Feed Count'); ?></h4>
							<div class="sfs_table-wrap">
								<table class="form-table">
									<tr>
										<th scope="row"><label class="description" for="sfs_options[sfs_custom]"><?php _e('Custom count'); ?></label></th>
										<td><input type="text" size="10" maxlength="100" name="sfs_options[sfs_custom]" value="<?php echo $options['sfs_custom']; ?>" /> 
											&mdash; <em><?php _e('Text/numbers only, no markup.'); ?></em> 
											<span class="tooltip" title="<?php _e('Tip: use the current subscriber count for a day or so after resetting the feed stats (check the next box to enable).'); ?>">?</span>
										</td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="sfs_options[sfs_custom_enable]"><?php _e('Enable custom count?'); ?></label></th>
										<td><input name="sfs_options[sfs_custom_enable]" type="checkbox" value="1" <?php if (isset($options['sfs_custom_enable'])) { checked('1', $options['sfs_custom_enable']); } ?> /> 
											&mdash; <em><?php _e('Select to display your custom feed count instead of the recorded value.'); ?></em>
										</td>
									</tr>
								</table>
							</div>
							<h4><?php _e('Admin Options'); ?></h4>
							<div class="sfs_table-wrap">
								<table class="form-table">
									<tr>
										<th scope="row"><label class="description" for="sfs_options[sfs_number_results]"><?php _e('Number of results per page'); ?></label></th>
										<td><input type="text" size="10" maxlength="10" name="sfs_options[sfs_number_results]" value="<?php echo $options['sfs_number_results']; ?>" />
											&mdash; <em><?php _e('Applies to the back-end statistics (this page only).'); ?></em>
										</td>
									</tr>
								</table>
							</div>
							<h4><?php _e('Custom CSS'); ?></h4>
							<div class="sfs_table-wrap">
								<table class="form-table">
									<tr>
										<th scope="row"><label class="description" for="sfs_options[sfs_custom_styles]"><?php _e('Custom CSS for count badge'); ?></label></th>
										<td>
											<textarea class="textarea" cols="50" rows="3" name="sfs_options[sfs_custom_styles]"><?php echo esc_textarea($options['sfs_custom_styles']); ?></textarea><br />
											&mdash; <em><?php _e('CSS/text only, no markup.'); ?></em> 
											<span class="tooltip" title="<?php _e('Tip: see the &ldquo;Template Tags &amp; Shortcodes&rdquo; panel for count-badge shortcode and template tag. 
											Default styles replicate the Feedburner chicklet. Leave blank to disable.'); ?>">?</span>
										</td>
									</tr>
								</table>
							</div>
							<h4><?php _e('Custom Feed Content'); ?></h4>
							<div class="sfs_table-wrap">
								<table class="form-table">
									<tr>
										<th scope="row"><label class="description" for="sfs_options[sfs_feed_content_before]"><?php _e('Display before each feed item'); ?></label></th>
										<td>
											<textarea class="textarea" cols="50" rows="3" name="sfs_options[sfs_feed_content_before]"><?php echo esc_textarea($options['sfs_feed_content_before']); ?></textarea><br />
											&mdash; <em><?php _e('Text and basic markup allowed.'); ?></em> 
											<span class="tooltip" title="<?php _e('Tip: you can has shortcodes. Leave blank to disable.'); ?>">?</span>
										</td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="sfs_options[sfs_feed_content_after]"><?php _e('Display after each feed item'); ?></label></th>
										<td>
											<textarea class="textarea" cols="50" rows="3" name="sfs_options[sfs_feed_content_after]"><?php echo esc_textarea($options['sfs_feed_content_after']); ?></textarea><br />
											&mdash; <em><?php _e('Text and basic markup allowed.'); ?></em> 
											<span class="tooltip" title="<?php _e('Tip: you can has shortcodes. Leave blank to disable.'); ?>">?</span>
										</td>
									</tr>
								</table>
							</div>
							<h4><?php _e('Clear, Reset, Restore, Delete'); ?></h4>
							<div class="sfs_table-wrap">
								<table class="form-table">
									<tr>
										<th scope="row"><label class="description"><?php _e('Clear the cache'); ?></label></th>
										<td><a href="<?php get_admin_url(); ?>options-general.php?page=simple-feed-stats/simple-feed-stats.php&amp;cache=clear"><?php _e('Clear cache'); ?></a> 
											&mdash; <em><?php _e('Tip: refresh this page to renew the cache after clearing.'); ?></em> 
											<span class="tooltip" title="<?php _e('Note: it&rsquo;s safe to clear the cache at any time. WP will automatically cache fresh data.'); ?>">?</span>
										</td>
									</tr>
									<tr>
										<th scope="row"><label class="description"><?php _e('Reset feed stats'); ?></label></th>
										<td><a class="reset" href="<?php get_admin_url(); ?>options-general.php?page=simple-feed-stats/simple-feed-stats.php&amp;reset=true"><?php _e('Reset stats'); ?></a> 
											&mdash; <em><?php _e('Warning: this will delete all feed stats!'); ?></em> 
											<span class="tooltip" title="<?php _e('Note: deletes data only. To delete the sfs table, see the &ldquo;Delete Database Table&rdquo; option (below).'); ?>">?</span>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row"><label class="description" for="sfs_options[default_options]"><?php _e('Restore default settings'); ?></label></th>
										<td>
											<input name="sfs_options[default_options]" type="checkbox" value="1" id="sfs_restore_defaults" <?php if (isset($options['default_options'])) { checked('1', $options['default_options']); } ?> /> 
											&mdash; <em><?php _e('Restore default options upon plugin deactivation/reactivation.'); ?></em> 
											<span class="tooltip" title="<?php _e('Tip: leave this option unchecked to remember your settings. 
											Or, to go ahead and restore all default options, check the box, save your settings, and then deactivate/reactivate the plugin.'); ?>">?</span>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row"><label class="description" for="sfs_options[sfs_delete_table]"><?php _e('Delete database table'); ?></label></th>
										<td>
											<input name="sfs_options[sfs_delete_table]" type="checkbox" value="1" id="sfs_delete_table" <?php if (isset($options['sfs_delete_table'])) { checked('1', $options['sfs_delete_table']); } ?> /> 
											&mdash; <em><?php _e('Delete the stats table the next time plugin is deactivated.'); ?></em> 
											<span class="tooltip" title="<?php _e('Tip: leave this option unchecked to keep your feed stats if the plugin is deactivated. 
											Or, to go ahead and delete the sfs table (and all of its data), check the box, save your settings, and then deactivate the plugin.'); ?>">?</span>
										</td>
									</tr>
								</table>
							</div>
							<input type="submit" class="button-primary" value="<?php _e('Save Settings'); ?>" />
						</form>
					</div>
				</div>
				<div id="sfs-shortcodes" class="postbox">
					<h3><?php _e('Template Tags &amp; Shortcodes'); ?></h3>
					<div class="toggle default-hidden">
						<p><strong><?php _e('Simple feed count (number/text only)'); ?></strong></p>
						<p>
							<?php _e('To display your current subscriber count as simple text, add the following template tag anywhere in your theme (e.g., sidebar, footer, etc.):'); ?>
							<br /><code>&lt;?php if(function_exists('sfs_display_subscriber_count')) sfs_display_subscriber_count(); ?&gt;</code>
						</p>
						<p><?php _e('Alternately, to display your current subscriber count in a post or page, add the following shortcode:'); ?><br /><code>[sfs_subscriber_count]</code></p>

						<p><strong><?php _e('Feed count badge (like Feedburner)'); ?></strong></p>
						<div class="sfs-subscriber-count-wrap"><?php sfs_display_count_badge(); ?></div>
						<p>
							<?php _e('To display your stats with a badge that looks like the Feedburner chicklet, add the following template tag anywhere in your theme:'); ?> 
							<span class="tooltip" title="<?php _e('Tip: visit the &ldquo;Tools &amp; Options&rdquo; panel to style your count badge with some custom CSS.'); ?>">?</span>
							<br /><code>&lt;?php if(function_exists('sfs_display_count_badge')) sfs_display_count_badge(); ?&gt;</code>
						</p>
						<p><?php _e('Alternately, to display a Feedburner-style badge in a post or page, add the following shortcode:'); ?><br /><code>[sfs_count_badge]</code></p>
					</div>
				</div>
				<div class="postbox">
					<h3><?php _e('Your Info / More Info'); ?></h3>
					<div class="toggle default-hidden">
						<p>
							<?php _e('Here are some helpful things to know when working with feeds.'); ?> 
							<span class="tooltip" title="<?php _e('Tip: to generate some feed data to look at, try clicking on a few of these links'); ?> :)">?</span>
						</p>
						<?php 
							$feed_rdf  = get_bloginfo('rdf_url'); // RDF feed and RDF comments (=bug)
							$feed_rss  = get_bloginfo('rss_url'); // RSS2 feed
							$feed_rss2 = get_bloginfo('rss2_url'); // RSS feed
							$feed_atom = get_bloginfo('atom_url'); // Atom feed
							$feed_coms = get_bloginfo('comments_rss2_url'); // used for RSS and RSS2 comments
							$feed_coms_atom = get_bloginfo('comments_atom_url'); // used for Atom comments
							$feed_coms_rdf = get_bloginfo('comments_rss2_url') . 'rdf/'; // used for RDF comments (see $feed_rdf)
			
							$curtime = mysql_real_escape_string(date("F jS Y, h:ia", time() - 25200)); // 25200 seconds = -7 hours
							$address = mysql_real_escape_string($_SERVER['REMOTE_ADDR']);
							$agent   = mysql_real_escape_string($_SERVER['HTTP_USER_AGENT']);
						?>
	
						<p><strong><?php _e('Your feed URLs'); ?></strong></p>
						<div class="sfs_table-wrap">
							<table class="sfs_info">
								<tr>
									<td class="sfs_info-type"><?php _e('Content RDF'); ?></td>
									<td><a href="<?php echo $feed_rdf; ?>"><code><?php echo $feed_rdf; ?></code></a></td>
								</tr>
								<tr>
									<td class="sfs_info-type"><?php _e('Content RSS'); ?></td>
									<td><a href="<?php echo $feed_rss; ?>"><code><?php echo $feed_rss; ?></code></a></td>
								</tr>
								<tr>
									<td class="sfs_info-type"><?php _e('Content RSS2'); ?></td>
									<td><a href="<?php echo $feed_rss2; ?>"><code><?php echo $feed_rss2; ?></code></a></td>
								</tr>
								<tr>
									<td class="sfs_info-type"><?php _e('Content Atom'); ?></td>
									<td><a href="<?php echo $feed_atom; ?>"><code><?php echo $feed_atom; ?></code></a></td>
								</tr>
								<tr>
									<td class="sfs_info-type"><?php _e('Comments RDF'); ?></td>
									<td><a href="<?php echo $feed_coms_rdf; ?>"><code><?php echo $feed_coms_rdf; ?></code></a></td>
								</tr>
								<tr>
									<td class="sfs_info-type"><?php _e('Comments RSS2'); ?></td>
									<td><a href="<?php echo $feed_coms; ?>"><code><?php echo $feed_coms; ?></code></a></td>
								</tr>
								<tr>
									<td class="sfs_info-type"><?php _e('Comments Atom'); ?></td>
									<td><a href="<?php echo $feed_coms_atom; ?>"><code><?php echo $feed_coms_atom; ?></code></a></td>
								</tr>
							</table>
						</div>
						<p><strong><?php _e('More about WordPress feeds'); ?></strong></p>
						<ul class="sfs-list">
							<li><a target="_blank" href="http://perishablepress.com/simple-feed-stats/"><?php _e('Simple Feed Stats Homepage'); ?></a></li>
							<li><a target="_blank" href="http://codex.wordpress.org/WordPress_Feeds"><?php _e('WP Codex: WordPress Feeds'); ?></a></li>
							<li><a target="_blank" href="http://perishablepress.com/what-is-my-wordpress-feed-url/"><?php _e('What is my WordPress Feed URL?'); ?></a></li>
							<li><a target="_blank" href="http://feedburner.google.com/"><?php _e('Google/Feedburner'); ?></a></li>
						</ul>
						<p><strong><?php _e('Your browser/IP info'); ?></strong></p>
						<ul class="sfs-list">
							<li><?php _e('IP Address:'); ?> <code><?php echo $address; ?></code></li>
							<li><?php _e('User Agent:'); ?> <code><?php echo $agent; ?></code></li>
							<li class="nudge"><?php _e('Approx. Time:'); ?> <code><?php echo $curtime; ?></code>
								<span class="tooltip" title="<?php _e('Denotes date/time of most recent page-load (useful when monitoring feed stats).'); ?>">?</span>
							</li>
						</ul>
					</div>
				</div>
				<div class="postbox">
					<h3><?php _e('Updates &amp; Info'); ?></h3>
					<div class="toggle default-hidden">
						<div id="sfs-current">
							<iframe src="http://perishablepress.com/current/"></iframe>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="sfs-credit-info">
			<a target="_blank" href="http://perishablepress.com/simple-feed-stats/" title="Simple Feed Stats Homepage">Simple Feed Stats</a> by 
			<a target="_blank" href="http://twitter.com/perishable" title="Jeff Starr on Twitter">Jeff Starr</a> @ 
			<a target="_blank" href="http://monzilla.biz/" title="Obsessive Web Design &amp; Development">Monzilla Media</a>
		</div>
	</div>

	<script type="text/javascript">
		// auto-submit
		function myF(targ, selObj, restore){
			eval(targ + ".location='" + selObj.options[selObj.selectedIndex].value + "'");
			if (restore) selObj.selectedIndex = 0;
		}
		// prevent accidents (delete stats)
		jQuery('.reset').click(function(event){
			event.preventDefault();
			var r = confirm("<?php _e('Are you sure you want to delete all the feed stats? (this action cannot be undone)'); ?>");
			if (r == true){  
				window.location = jQuery(this).attr('href');
			}
		});
		// prevent accidents (restore options)
		if(!jQuery("#sfs_restore_defaults").is(":checked")){
			jQuery('#sfs_restore_defaults').click(function(event){
				var r = confirm("<?php _e('Are you sure you want to restore all default options? (this action cannot be undone)'); ?>");
				if (r == true){  
					jQuery("#sfs_restore_defaults").attr('checked', true);
				} else {
					jQuery("#sfs_restore_defaults").attr('checked', false);
				}
			});
		}
		// prevent accidents (delete table)
		if(!jQuery("#sfs_delete_table").is(":checked")){
			jQuery('#sfs_delete_table').click(function(event){
				var r = confirm("<?php _e('Are you sure you want to delete the stats table and all of its data? (this action cannot be undone)'); ?>");
				if (r == true){  
					jQuery("#sfs_delete_table").attr('checked', true);
				} else {
					jQuery("#sfs_delete_table").attr('checked', false);
				}
			});
		}
		// Easy Tooltip 1.0 - Alen Grakalic @ http://cssglobe.com/post/4380/easy-tooltip--jquery-plugin
		(function($) {
			$.fn.easyTooltip = function(options){
				var defaults = {	
					xOffset: 10,		
					yOffset: 25,
					tooltipId: "easyTooltip",
					clickRemove: false,
					content: "",
					useElement: ""
				}; 
				var options = $.extend(defaults, options);  
				var content;	
				this.each(function() {  				
					var title = $(this).attr("title");				
					$(this).hover(function(e){											 							   
						content = (options.content != "") ? options.content : title;
						content = (options.useElement != "") ? $("#" + options.useElement).html() : content;
						$(this).attr("title","");								  				
						if (content != "" && content != undefined){			
							$("body").append("<div id='"+ options.tooltipId +"'>"+ content +"</div>");		
							$("#" + options.tooltipId).css("position","absolute").css("top",(e.pageY - options.yOffset) + "px")
								.css("left",(e.pageX + options.xOffset) + "px").css("display","none").fadeIn("fast")
						}
					},
					function(){	
						$("#" + options.tooltipId).remove();
						$(this).attr("title",title);
					});	
					$(this).mousemove(function(e){
						$("#" + options.tooltipId)
						.css("top",(e.pageY - options.yOffset) + "px")
						.css("left",(e.pageX + options.xOffset) + "px")					
					});	
					if(options.clickRemove){
						$(this).mousedown(function(e){
							$("#" + options.tooltipId).remove();
							$(this).attr("title",title);
						});				
					}
				});
			};
		})(jQuery);
		jQuery(".tooltip").easyTooltip();
		// togglez
		jQuery(document).ready(function(){
			jQuery('#sfs-toggle-panels a').click(function(){
				jQuery('.toggle').slideToggle(300);
				return false;
			});
			jQuery('.default-hidden').hide();
			jQuery('h3').click(function(){
				jQuery(this).next().slideToggle(300);
			});
			jQuery('#sfs-options-link').click(function(){
				jQuery('.toggle').hide();
				jQuery('#sfs_custom-options .toggle').slideToggle(300);
				return true;
			});
			jQuery('#sfs-shortcodes-link').click(function(){
				jQuery('.toggle').hide();
				jQuery('#sfs-shortcodes .toggle').slideToggle(300);
				return true;
			});
			jQuery('.sfs-open-tracking').click(function(){
				jQuery('.sfs-open-tracking-image, .sfs-open-tracking-url').slideDown('fast');
			});
			jQuery('.sfs-tracking').click(function(){
				jQuery('.sfs-open-tracking-image, .sfs-open-tracking-url').slideUp('fast');
			});
		});
	</script>

<?php } ?>