<?php

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
	die( "Can not create `callback` table: " . $check->getMessage() .  "\n");
}


// Version 1.1 upgrade - add sleep time.
$sql = "SELECT sleep FROM callback";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
	sql('ALTER TABLE callback ADD COLUMN sleep INT DEFAULT 0');
	}

?>
