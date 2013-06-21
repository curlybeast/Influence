<?php

function debug($message)
{
	print "$message\n";
}


// validate email address
function address_validate($address_black_list, $address)
{
	/* Check black list... */
	foreach ($address_black_list as $ignore) {
		if (strstr($address, $ignore)) {
			return false;
		}
	}

	return (filter_var($address, FILTER_VALIDATE_EMAIL)) ? true : false;
}

// split email into name / address
function address_split($address_black_list, $str)
{
	$name = $email = '';

	if (substr($str, 0, 1) == '<') {
		// first character = <
		$email = str_replace(array('<', '>'), '', $str);
	} else if (strpos($str,' <') !== false) {
		// possibly = name <email>
		list($name, $email) = explode(' <', $str);
		$email = str_replace('>', '', $email);
		$name = str_replace(array('"', "'"), '', $name);
	}

	// Drop address entirely if on black list or not valid
	if (!address_validate($address_black_list, $email)) {
		return null;
	}

	// Try to be clever with Foo in foo@bar.baz as a name
	if ($name == $email) {
		$parts = explode("@", $email);
		$address = str_replace(array('.','-'), ' ', $parts[0]);
		$name = ucwords(strtolower($address));
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

function mail_get_contacts($connection, $address_black_list)
{
	$contacts = null;

	/* Fetch an overview for all messages in INBOX */
	debug("Checking for emails");
	$check = imap_check($connection);

	if ($check->Nmsgs < 1) {
		debug("  No emails found, nothing to do.");
		return null;
	} else {
		debug("  Found {$check->Nmsgs} emails");
	}

	debug("  Retrieveing contacts from emails (NOTE: this may take a few minutes)...");

	// Use only 5 for testing.
	//$result = imap_fetch_overview($connection, "1:100", 0);

	$result = imap_fetch_overview($connection, "1:{$check->Nmsgs}", 0);

	foreach ($result as $overview) {
		$contact = address_split($address_black_list, $overview->from);

		if ($contact == null) {
			continue;
		}

		if (sizeof($contacts) < 1) {
			$contacts = $contact;
		} else {
			$contacts += $contact;
		}
	}

	debug("  Found ".sizeof($contacts)." contacts");

	ksort($contacts, SORT_LOCALE_STRING);
	echo print_r($contacts, true);

	return $contacts;
}

function dump_contacts_to_file($contacts)
{
	$file = 'people.txt';

	// Open the file to get existing content
	$current = file_get_contents($file);
	// Append a new person to the file
	$current .= "John Smith\n";
	// Write the contents back to the file
	file_put_contents($file, $current);
}

/* Start */
if ($argc < 2) {
	die("Expecting more arguments\n\nUsage: ".$argv[0]." <username> <password>\n\n");
}

$address_black_list = array('noreply', 'no-reply', 'do_not_reply', 'do-not-reply', 'donotreply', 'donotrespond');

$username = $argv[1];
$password = $argv[2];
$connection = mail_connect($username, $password);
$contacts = mail_get_contacts($connection, $address_black_list);
mail_disconnect($connection);

?>