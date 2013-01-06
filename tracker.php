<?php // sfs tracking

// utilize wordpress
define('WP_USE_THEMES', false);
require('../../../wp-blog-header.php');
$options = get_option('sfs_options');
global $wpdb, $wp_query;

// feed tracker
if(isset($_GET["sfs_tracking"])) {

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

	$wp_feeds = array($feed_rdf, $feed_rss2, $feed_atom, $feed_coms, $feed_coms_atom, $feed_coms_rdf); // removed $feed_rss
	$table = $wpdb->prefix . 'simple_feed_stats';
	if (!$referer) $referer = 'blank';

	// custom tracking (excludes non-RSS comment feeds)
	if ($options['sfs_tracking_method'] == 'sfs_custom_tracking') {

		$feed_type = $_GET["feed_type"];
		$tracking = 'custom';

		if ($feed_type == 'rdf') {
			$type = 'RDF';
		} elseif ($feed_type == 'rss') {
			$type = 'RSS';
		} elseif ($feed_type == 'feed') {
			$type = 'RSS2';
		} elseif ($feed_type == 'atom') {
			$type = 'Atom';
		} elseif ($feed_type == 'comments') {
			$type = 'Comments';
		} else {
			$type = 'other';
		}
		if (isset($_GET["feed_type"])) {
			$query  = "INSERT INTO $table (logtime,   request,   referer,   type,   qstring,   address,   tracking,   agent) ";
			$query .= "VALUES           ('$logtime','$request','$referer','$type','$qstring','$address','$tracking','$agent')";
			$result = @mysql_query($query);
		}
	}
	// alternate tracking
	if (($options['sfs_tracking_method'] == 'sfs_alt_tracking')) {

		$tracking = 'alt';

		if ($referer == $feed_rdf) {
			$type = 'RDF';
		} elseif ($referer == $feed_rss) {
			$type = 'RSS';
		} elseif ($referer == $feed_rss2) {
			$type = 'RSS2';
		} elseif ($referer == $feed_atom) {
			$type = 'Atom';
		} elseif ($referer == $feed_coms) {
			$type = 'Comments';
		} elseif ($referer == $feed_coms_atom) {
			$type = 'Comments';
		} elseif ($referer == $feed_coms_rdf) {
			$type = 'Comments';
		} else {
			$type = 'other';
		}
		if (in_array($referer, $wp_feeds)) {
			$query  = "INSERT INTO $table (logtime,   request,   referer,   type,   qstring,   address,   tracking,   agent) ";
			$query .= "VALUES           ('$logtime','$request','$referer','$type','$qstring','$address','$tracking','$agent')";
			$result = @mysql_query($query);
		}
	}
	// open tracking
	if (($options['sfs_tracking_method'] == 'sfs_open_tracking')) {

		$feed_type = $_GET["sfs_type"];
		$tracking = 'open';
		$type = 'custom';

		if ($feed_type == 'open') {
			$query  = "INSERT INTO $table (logtime,   request,   referer,   type,   qstring,   address,   tracking,   agent) ";
			$query .= "VALUES           ('$logtime','$request','$referer','$type','$qstring','$address','$tracking','$agent')";
			$result = @mysql_query($query);
		}
	}
	// redirect to default or custom tracking image
	if (($options['sfs_tracking_method'] == 'sfs_open_tracking')) {
		$custom_image = $options['sfs_open_image_url'];
		wp_redirect($custom_image);
		exit;
	} else {
		$tracker_image = plugins_url() . '/simple-feed-stats/tracker.gif';
		wp_redirect($tracker_image);
		exit;
	}
} 

?>