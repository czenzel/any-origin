<?php
/**
teamWeather.com and Zenzel Technologies
Any-Origin for PHP Frameworks

Version: Origin-Cache - Throttled by Caching of Any-Origin

Copyright 2014 Christopher Zenzel.
All Rights Reserved.

http://zenzel.com
http://teamweather.com

support - at - teamweather.com

License:
This work is licensed under the Creative Commons Attribution-ShareAlike 4.0 International License. To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/4.0/.

Last Updated:	2014-08-29
 **/

/* Do not modify this line because of request limits set for Azure. */
ini_set('memory_limit', '1024K');
/* Do not modify above this line. */

/* Set a user cache time for each URL requested. A standard would be between 10 and 12 minutes */
$user_cache_time = 60 * 12;

/* Set this to a global directory to be shared by all your scripts */
$user_cache_path = dirname(__FILE__) . '/origin-cache';

/* Set a useful user agent and your domain that is performing the Any-Origin requests. This is mostly to help servers and to track abuse of a service. If your API is Public please list it as Public */
$user_agent = 'AnyOrigin/1.0; Public API';
$user_domain = '---';

/* It is recommended that you keep this value to prevent requests looking like they are all from you if hosting multiple sites */

$incoming_headers = parseRequestHeaders();
$incoming_type = "";
$ao_user_origin = "";

if (isset($incoming_headers["origin"])) {
	$ao_user_origin = $incoming_headers["origin"];
	$incoming_type = "origin";
} else if (isset($incoming_headers["x-requested-with"])) {
	$ao_user_origin = $incoming_headers["x-requested-with"];
	$incoming_type = "x-requested-with";
} else {
	die("[500] No valid requested with or origin specified.");
}

$allowed_hosts = array('*');

/* Do not modify below this line */

$user_agent_full = "$user_agent ($user_domain)";

$client_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$client_referer = parse_url($client_referer);
$client_host = $client_referer['host'];

$client_url = $_GET['u'];
$client_allowed = 0;

if (in_array(strtolower($client_host), $allowed_hosts) || $allowed_hosts[0] == '*') {
	$client_allowed = 1;
}

/* Check for POST data for any-origin. Please note all POST data passes. */

$ao_post = "";
$ao_post_active = 0;
if ($_POST) {
	$ao_post_active = 1;
	$ao_post = http_build_query($_POST);
}

/* Continue with any-origin request */

header('Access-Control-Allow-Origin: *');

/* Safety Feature */

header('Content-Type: text/plain');

/* Continue */

if (!$client_url || !$client_allowed) {
	die('[500] Unknown Error. Unable to continue.');
} else {
	echo curl_cacher(urldecode($client_url));
}

/* Primary Functions */

function curl_cacher ($url) {
	global $user_cache_time, $user_cache_path, $ao_post, $ao_post_active;

	$url_hasher = md5($url);
	$post_hasher = md5($ao_post);

	$cache_file = $url_hasher;
	if ($ao_post_active) $cache_file .= '_' . md5($ao_post);

	/* Make a simpler shorter hash of the hashed data. Double hash */
	$cache_file = md5($cache_file);

	/* Create the full path */
	$cache_file = $user_cache_path . '/' . $cache_file . '.txt';

	/* Check the cache */
	$blaststop = use_cache($cache_file, $user_cache_time);

	/* Now see what to do cache, or not cache, etc */
	if ($blaststop) {
		$data = file_get_contents($cache_file);
		return $data;
	}
	else {
		$data = _curl_get_data($url);
		file_put_contents($cache_file, $data);
		return $data;
	}

	return "";
}

function _curl_get_data ($url) {
	global $user_agent_full, $ao_post, $ao_post_active, $ao_user_origin;

	$ch = curl_init();
	$timeout = 5;

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_POST, $ao_post_active);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $ao_post);

	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	curl_setopt($ch, CURLOPT_REFERER, $ao_user_origin);

	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent_full);

	$data = curl_exec($ch);

	curl_close($ch);
	return $data;
}

function parseRequestHeaders() {
	$headers = array();
	foreach($_SERVER as $key => $value) {
		if (substr($key, 0, 5) <> 'HTTP_') {
			continue;
		}
		$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
		$headers[strtolower($header)] = $value;
	}
	return $headers;
}

function use_cache ($cache_file, $cache_time) {
	$cache_created = (file_exists($cache_file)) ? filemtime($cache_file) : 0;
	if (time() - $cache_time < $cache_created) {
		return true;
	}
	return false;
}
?>
