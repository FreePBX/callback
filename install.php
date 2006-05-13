<?php

global $db;

// Version 1.1 upgrade - add sleep time.
$sql = "SELECT sleep FROM callback";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
	sql('ALTER TABLE callback ADD COLUMN sleep INT DEFAULT 0');
	}

?>
