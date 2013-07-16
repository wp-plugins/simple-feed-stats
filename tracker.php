<?php // Simple Feed Stats > Tracking

define('WP_USE_THEMES', false);
require('../../../wp-blog-header.php');
$options = get_option('sfs_options');
global $wpdb, $wp_query;

//$wpdb->show_errors();
$wpdb->hide_errors();
error_reporting(0);

function sfs_cleaner($string) {
	$string = rtrim($string); 
	$string = ltrim($string); 
	$string = htmlspecialchars($string);
	$string = htmlentities($string, ENT_QUOTES);
	$string = mysql_real_escape_string($string); 
	$string = str_replace("\n", "", $string);
	if (get_magic_quotes_gpc()) {
		$string = stripslashes($string);
	} 
	return $string;
}

if (isset($_GET["sfs_tracking"])) {

	if (isset($_SERVER["HTTP_HOST"]))       { $host    = sfs_cleaner($_SERVER["HTTP_HOST"]);       } else { $host     = 'n/a'; }
	if (isset($_SERVER["REQUEST_URI"]))     { $req     = sfs_cleaner($_SERVER["REQUEST_URI"]);     } else { $req      = 'n/a'; }
	if (isset($_SERVER['HTTP_REFERER']))    { $referer = sfs_cleaner($_SERVER['HTTP_REFERER']);    } else { $referer  = 'n/a'; }
	if (isset($_SERVER['QUERY_STRING']))    { $qstring = sfs_cleaner($_SERVER['QUERY_STRING']);    } else { $qstring  = 'n/a'; }
	if (isset($_SERVER['REMOTE_ADDR']))     { $address = sfs_cleaner($_SERVER['REMOTE_ADDR']);     } else { $address  = 'n/a'; }
	if (isset($_SERVER['HTTP_USER_AGENT'])) { $agent   = sfs_cleaner($_SERVER['HTTP_USER_AGENT']); } else { $agent    = 'n/a'; }

	date_default_timezone_set('UTC');
	$logtime = date("F jS Y, h:ia", time() - 25200); // eg: 25200 seconds = -7 hours
	$request = 'http://' . $host . $req;

	$feed_rdf       = get_bloginfo('rdf_url');                    // RDF feed and RDF comments (=wpbug)
	$feed_rss       = get_bloginfo('rss_url');                    // RSS2 feed
	$feed_rss2      = get_bloginfo('rss2_url');                   // RSS feed
	$feed_atom      = get_bloginfo('atom_url');                   // Atom feed
	$feed_coms      = get_bloginfo('comments_rss2_url');          // used for RSS and RSS2 comments
	$feed_coms_atom = get_bloginfo('comments_atom_url');          // used for Atom comments
	$feed_coms_rdf  = get_bloginfo('comments_rss2_url') . 'rdf/'; // used for RDF comments (see $feed_rdf)

	$wp_feeds = array($feed_rdf, $feed_rss2, $feed_atom, $feed_coms, $feed_coms_atom, $feed_coms_rdf); // removed $feed_rss
	$table = $wpdb->prefix . 'simple_feed_stats';

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
			$query  = "INSERT INTO $table (logtime, request, referer, type, qstring, address, tracking, agent) ";
			$query .= "VALUES ('$logtime', '$request', '$referer', '$type', '$qstring', '$address', '$tracking', '$agent')";
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
			$query  = "INSERT INTO $table (logtime, request, referer, type, qstring, address, tracking, agent) ";
			$query .= "VALUES ('$logtime', '$request', '$referer', '$type', '$qstring', '$address', '$tracking', '$agent')";
			$result = @mysql_query($query);
		}
	}
	// open tracking
	if (($options['sfs_tracking_method'] == 'sfs_open_tracking')) {

		$feed_type = $_GET["sfs_type"];
		$tracking = 'open';
		$type = 'custom';

		if ($feed_type == 'open') {
			$query  = "INSERT INTO $table (logtime, request, referer, type, qstring, address, tracking, agent) ";
			$query .= "VALUES ('$logtime', '$request', '$referer', '$type', '$qstring', '$address', '$tracking', '$agent')";
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
} else {
	die();
}

exit;
