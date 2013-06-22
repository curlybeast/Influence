<?php
/*
 * Copyright 2013, Martyn Russell <martyn@lanedo.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function debug($message)
{
	print "$message\n";
}

function format_time_elapsed($ptime)
{
	$etime = time() - $ptime;

	if ($etime < 1) {
		return '0 seconds';
	}

	$a = array(12 * 30 * 24 * 60 * 60  =>  'year',
	           30 * 24 * 60 * 60       =>  'month',
	           24 * 60 * 60            =>  'day',
	           60 * 60                 =>  'hour',
	           60                      =>  'minute',
	           1                       =>  'second');

	foreach ($a as $secs => $str) {
		$d = $etime / $secs;
		if ($d >= 1) {
			$r = round($d);
			return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
		}
	}
}

function format_bytes($size, $precision = 2)
{
	$base = log($size) / log(1024);
	$suffixes = array('bytes', 'kb', 'Mb', 'Gb', 'Tb');

	return round(pow(1024, $base - floor($base)), $precision) . " " . $suffixes[floor($base)];
}

/* Validate email address */
function address_is_black_listed($address)
{
	$black_list = array('noreply',
	                    'no-reply',
	                    'do_not_reply',
	                    'do-not-reply',
	                    'donotreply',
	                    'donotrespond');

	foreach ($black_list as $ignore) {
		if (strstr($address, $ignore)) {
			return true;
		}
	}

	return false;
}

function address_is_useful($address)
{
	if (address_is_black_listed($address)) {
		return false;
	}

	if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
		return false;
	}

	return true;
}

/* Split email into name / address */
function address_split($str)
{
	// FIXME: Is this function still useful?
	$name = $email = '';

	if (substr($str, 0, 1) == '<') {
		/* first character = < */
		$email = str_replace(array('<', '>'), '', $str);
	} else if (strpos($str,' <') !== false) {
		/* possibly = name <email> */
		list($name, $email) = explode(' <', $str);
		$email = str_replace('>', '', $email);
		$name = str_replace(array('"', "'"), '', $name);
	}

	/* Drop address entirely if on black list or not valid */
	if (address_is_useful($email)) {
		return null;
	}

	/* Try to be clever with Foo in foo@bar.baz as a name */
	if ($name == $email) {
		$parts = explode("@", $email);
		$address = str_replace(array('.','-'), ' ', $parts[0]);
		$name = ucwords(strtolower($address));
	}

	return array(trim(strtolower($email)) => trim($name));
}

/* Join [personal] <[mailbox]@[host]> */
function address_join($personal, $mailbox, $host)
{
	if ((!isset($mailbox) || $mailbox == "") ||
	    (!isset($host) || $host == "")) {
		return null;
	}

	$email = "$mailbox@$host";

	/* Try to be clever with Foo in foo@bar.baz as a name */
	if ((!isset($personal) || $personal == "") || ($personal == $email)) {
		$str = str_replace(array('.','-'), ' ', $mailbox);
		$name = ucwords(strtolower($str));
	} else {
		$name = $personal;
	}

	return array(trim(strtolower($email)) => trim($name));
}

function mail_connect($username, $password)
{
	/* connect to gmail */
	$hostname = "{imap.gmail.com:993/imap/ssl}INBOX";

	debug("Logging In...");

	/* try to connect */
	$connection = imap_open($hostname, $username, $password) or die("Can not connect to IMAP host: " . imap_last_error());

	debug("Connected!");

	return $connection;
}

function mail_disconnect($connection)
{
	debug("Logging Out...");

	/* close the connection */
	imap_close($connection);
}

function mail_get_contacts($connection, $last_update)
{
	$contacts = null;
	$hosts = null;

	/* fetch an overview for all messages in INBOX */
	debug("Checking INBOX");
	$check = imap_check($connection);

	if ($check->Nmsgs < 1) {
		debug("  No emails found, nothing to do.");
		return null;
	} else {
		debug("  Found {$check->Nmsgs} emails");
	}

	// Test
	/* $last_update = strtotime("2008-01-01 12:00:00"); */
	/* $last_update = strToTime( "-7 days"); */

	$last_update_formatted = date("D, j M Y H:i:s O T", $last_update);

	debug("  Retrieveing contacts from emails, ignoring emails before: " . $last_update_formatted);
	debug("  (NOTE: this may take a few minutes)...");

	$uids = imap_sort($connection, SORTARRIVAL, 0, SE_UID, "SINCE \"$last_update_formatted\"");

	foreach ($uids as $uid) {
		$msgno = imap_msgno($connection, $uid);
		$header = imap_header($connection, $msgno);

		if (!isset ($header->from)) {
			continue;
		}

		$contact = address_join(isset($header->from[0]->personal) ? $header->from[0]->personal : "",
		                        $header->from[0]->mailbox,
		                        $header->from[0]->host);

		list($address, $name) = each($contact);

		debug("  Found '$name <$address>', '" . $header->date . "'");

		if (!isset($contact) || !address_is_useful($address)) {
			debug("  --> Contact was null/invalid/blacklisted, ignoring");
			continue;
		}

		if (sizeof($contacts) < 1) {
			$contacts = $contact;
		} else {
			$contacts += $contact;
		}

		if (sizeof($hosts) < 1) {
			$hosts = $header->from[0]->host;
		} else {
			$hosts += $header->from[0]->host;
		}
	}

	debug("  Found " . sizeof($contacts) . " new contacts");

	if ($contacts != null) {
		ksort($contacts, SORT_LOCALE_STRING);
	}

	echo print_r($contacts, true);

	return $contacts;
}

function cache_save($cache_filename, $contacts)
{
	debug("Cache: Saving contacts to '$cache_filename'...");

	$content = serialize($contacts);
	$bytes = file_put_contents($cache_filename, $content);

	if ($bytes < 1) {
		debug("  Could not save " . sizeof($contacts) . " contacts");
		return false;
	} else {
		debug("  Done (wrote " . sizeof($contacts) . " contacts, " . format_bytes($bytes) . ")");
	}

	return true;
}

function cache_load($cache_filename)
{
	debug("Cache: Loading contacts from '$cache_filename'...");

	if (!file_exists($cache_filename)) {
		debug("  No previous contacts cached.");
		return array(null, 0);
	}

	$last_update = filemtime($cache_filename);

	debug("  Last update was " . format_time_elapsed($last_update));

	$content = file_get_contents($cache_filename);
	$contacts = unserialize($content);

	debug("  Done (read " . sizeof($contacts) . " contacts)");

	return array($contacts, $last_update);
}

/* Variables / Settings */
$home_dir = getenv("HOME");
$cache_filename = "$home_dir/.cache/contacts.txt";

/* Check arguments */
if ($argc < 2) {
	die("Expecting more arguments\n\nUsage: ".$argv[0]." <username> <password>\n\n");
}

/* Start */
$username = $argv[1];
$password = $argv[2];

list($contacts, $last_update) = cache_load($cache_filename);

$connection = mail_connect($username, $password);
$contacts = mail_get_contacts($connection, $last_update);

cache_save($cache_filename, $contacts);

mail_disconnect($connection);

?>