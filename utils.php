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

?>