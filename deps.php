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

function check_deps()
{
	if (!function_exists("imap_open")) {
		die("IMAP function imap_open() doesn't exist.\nCheck 'php5-imap' package is installed\n");
	}
}

?>
