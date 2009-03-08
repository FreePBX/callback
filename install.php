<?php
//This file is part of FreePBX.
//
//    FreePBX is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 2 of the License, or
//    (at your option) any later version.
//
//    FreePBX is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with FreePBX.  If not, see <http://www.gnu.org/licenses/>.
//
// Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
global $db;
global $amp_conf;

$autoincrement = ($amp_conf["AMPDBENGINE"] == "sqlite3") ? "AUTOINCREMENT":"AUTO_INCREMENT";

$sql = "CREATE TABLE IF NOT EXISTS callback (
	callback_id INTEGER NOT NULL PRIMARY KEY $autoincrement,
	description VARCHAR( 50 ) ,
	callbacknum VARCHAR( 100 ) ,
	destination VARCHAR( 50 ) ,
	sleep INTEGER,
	deptname VARCHAR( 50 )
);";

$check = $db->query($sql);
if (DB::IsError($check)) {
	die_freepbx( "Can not create `callback` table: " . $check->getMessage() .  "\n");
}


// Version 1.1 upgrade - add sleep time.
$sql = "SELECT sleep FROM callback";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
	sql('ALTER TABLE callback ADD COLUMN sleep INT DEFAULT 0');
	}

$results = array();
$sql = "SELECT callback_id, destination FROM callback";
$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($results)) { // error - table must not be there
	foreach ($results as $result) {
		$old_dest  = $result['destination'];
		$callback_id    = $result['callback_id'];

		$new_dest = merge_ext_followme(trim($old_dest));
		if ($new_dest != $old_dest) {
			$sql = "UPDATE callback SET destination = '$new_dest' WHERE callback_id = $callback_id  AND destination = '$old_dest'";
			$results = $db->query($sql);
			if(DB::IsError($results)) {
				die_freepbx($results->getMessage());
			}
		}
	}
}

?>
