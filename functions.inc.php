<?php /* $Id */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
// returns a associative arrays with keys 'destination' and 'description'
function callback_destinations() {
	//get the list of meetmes
	$results = callback_list();

	// return an associative array with destination and description
	if (isset($results)) {
		$extens = [];
		foreach($results as $result){
				$extens[] = ['destination' => 'callback,'.$result['callback_id'].',1', 'description' => $result['description']];
		}
		return $extens;
	} else {
		return null;
	}
}

function callback_getdest($exten) {
	return ['callback,'.$exten.',1'];
}

function callback_getdestinfo($dest) {
	global $active_modules;
	if (str_starts_with(trim((string) $dest), 'callback,')) {
		$exten = explode(',',(string) $dest);
		$exten = $exten[1];
		$thisexten = callback_get($exten);
		if (empty($thisexten)) {
			return [];
		} else {
			return ['description' => sprintf(_("Callback: %s"),$thisexten['description']), 'edit_url' => 'config.php?display=callback&view=form&itemid='.urlencode($exten)];
		}
	} else {
		return false;
	}
}

function callback_check_destinations($dest=true) {
	global $active_modules;

	$destlist = [];
	if (is_array($dest) && empty($dest)) {
		return $destlist;
	}
	$sql = "SELECT callback_id, destination, description FROM callback ";
	if ($dest !== true) {
		$sql .= "WHERE destination in ('".implode("','",$dest)."')";
	}
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	$type = $active_modules['callback']['type'] ?? 'setup';

	foreach ($results as $result) {
		$thisdest = $result['destination'];
		$thisid   = $result['callback_id'];
		$destlist[] = ['dest' => $thisdest, 'description' => sprintf(_("Callback: %s"),$result['description']), 'edit_url' => 'config.php?display=callback&type='.$type.'&itemid='.urlencode((string) $thisid)];
	}
	return $destlist;
}

function callback_change_destination($old_dest, $new_dest) {
	$sql = 'UPDATE callback SET destination = "' . $new_dest . '" WHERE destination = "' . $old_dest . '"';
	sql($sql, "query");
}


/* 	Generates dialplan for callback
	We call this with retrieve_conf
*/
function callback_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	global $amp_conf;
	switch($engine) {
		case "asterisk":
			$timelist = callback_list();
			if(is_array($timelist)) {
				foreach($timelist as $item) {

					// use callbacknum if avail, otherwise use cidnum
					$callback_num = (empty($item['callbacknum']) ? '${CALLERID(number)}' : $item['callbacknum']);
					$ext->add('callback', $item['callback_id'], '', new ext_setvar("CALL",$callback_num));

					//substitute commas with periods to keep asterisk dialplan variables happy
					$callback_destination = str_replace(",",".",(string) $item['destination']);
					$ext->add('callback', $item['callback_id'], '', new ext_setvar("DESTINATION",$callback_destination));

					// set sleep time
					$sleep = (empty($item['sleep']) ? '0' : $item['sleep']);
					$ext->add('callback', $item['callback_id'], '', new ext_setvar("SLEEP",$sleep));

					// set timeout
					$timeout = (empty($item['timeout']) ? '15000' : $item['timeout']);
					$ext->add('callback', $item['callback_id'], '', new ext_setvar("TIMEOUT",$timeout));

					// set callerid
					$callerid = (empty($item['callerid']) ? "Callback" : trim((string) $item['callerid']));
					$ext->add('callback', $item['callback_id'], '', new ext_setvar("CALLERID",$callerid));

					// kick off the callback script - run in background (&) so we can hangup
					$ext->add('callback', $item['callback_id'], '', new ext_system((empty($amp_conf['ASTVARLIBDIR']) ? '/var/lib/asterisk' : $amp_conf['ASTVARLIBDIR']).'/bin/callback ${CALL} ${DESTINATION} ${SLEEP} ${TIMEOUT} ${BASE64_ENCODE(${CALLERID})} &'));

					//hangup
					$ext->add('callback', $item['callback_id'], '', new ext_hangup(''));
				}

			}
		break;
	}
}

//get the existing meetme extensions
function callback_list() {
	_callback_backtrace();
	return \FreePBX::Callback()->listCallbacks();
}

function callback_get($id){
	//get all the variables for the meetme
	return sql("SELECT * FROM callback WHERE callback_id = '$id'","getRow",DB_FETCHMODE_ASSOC);
}

function callback_del($id){
	$results = sql("DELETE FROM callback WHERE callback_id = '$id'","query");
}

function callback_add($post){
	$var = [];
 $description = null;
 $callbacknum = null;
 $goto0 = null;
 $deptname = null;
 $sleep = 0;
 $timeout = 0;
 $callerid = null;
 global $db, $amp_conf;
	if(!callback_chk($post))
		return false;
	extract($post);

	$var[':description'] =  isset($post['description']) ? $description : '';
	$var[':callbacknum'] =  isset($post['callbacknum']) ? $callbacknum : '';
	$var[':goto0'] =  isset($post['goto0']) ? ${$goto0.'0'} : '';
	$var[':deptname'] =  isset($post['deptname']) ? $deptname : '';
	$var[':sleep'] =  (isset($post['sleep']) && is_numeric($post['sleep'])) ? $sleep : 0;
	$var[':timeout'] =  (isset($post['timeout']) && is_numeric($post['timeout'])) ? $timeout : 0;
	$var[':callerid'] =  isset($post['callerid']) ? $callerid : '';

	if(empty($var[':description'])) $var[':description'] = ${$goto0.'0'};

	$sql = "INSERT INTO callback (description,callbacknum,destination,deptname,sleep,timeout,callerid) values (:description,:callbacknum,:goto0,:deptname,:sleep,:timeout, :callerid)";

	$dbh = \FreePBX::Database();
	$sth = $dbh->prepare($sql);

	try{
		$ret = $sth->execute($var);
		$id = $dbh->lastInsertId();
	}catch(PDOException $e){
		$id = false;
		die_freepbx($e->getMessage());
	}
	return($id);
}

function callback_edit($id,$post){
	$goto0 = null;
 $callbacknum = null;
 $deptname = null;
 $sleep = null;
 $timeout = null;
 $callerid = null;
 if(!callback_chk($post))
		return false;
	extract($post);
	if(empty($description)) $description = ${$goto0.'0'};
	$results = sql("UPDATE callback SET description = \"$description\", callbacknum = \"$callbacknum\", destination = \"${$goto0.'0'}\", deptname = \"$deptname\", sleep = \"$sleep\", timeout = \"$timeout\", callerid = '$callerid' WHERE callback_id = \"$id\"");
}

// ensures post vars is valid
function callback_chk($post){
	return true;
}
function _callback_backtrace() {
	$trace = debug_backtrace();
	$function = $trace[1]['function'];
	$line = $trace[1]['line'];
	$file = $trace[1]['file'];
	freepbx_log(FPBX_LOG_WARNING,'Depreciated Function '.$function.' detected in '.$file.' on line '.$line);
}
