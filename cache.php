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

require_once("utils.php");

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

?>