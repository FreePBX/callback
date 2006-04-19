<?php

sql('CREATE TABLE IF NOT EXISTS callback ( callback_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY , description VARCHAR( 50 ) , callbacknum VARCHAR( 100 ) , destination VARCHAR( 50 ) , deptname VARCHAR( 50 ));') ;

/*
v1.0, original release
v1.1, added sleep time
*/

$callback_thisVersion = '1.1';
$callback_installedVersion = modules_getversion($modulename);

//$callback_installedVersion = modules_getversion('callback');

if (version_compare($callback_installedVersion, "1.1", "<")) {
	sql('ALTER TABLE callback ADD COLUMN sleep INT DEFAULT 0');
}

?>
