<?php /* $Id */
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

// returns a associative arrays with keys 'destination' and 'description'
function callback_destinations() {
	//get the list of meetmes
	$results = callback_list();

	// return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
				$extens[] = array('destination' => 'callback,'.$result['callback_id'].',1', 'description' => $result['description']);
		}
		return $extens;
	} else {
		return null;
	}
}

function callback_getdest($exten) {
	return array('callback,'.$exten.',1');
}

function callback_getdestinfo($dest) {
	global $active_modules;
	if (substr(trim($dest),0,9) == 'callback,') {
		$exten = explode(',',$dest);
		$exten = $exten[1];
		$thisexten = callback_get($exten);
		if (empty($thisexten)) {
			return array();
		} else {
			//$type = isset($active_modules['announcement']['type'])?$active_modules['announcement']['type']:'setup';
			return array('description' => sprintf(_("Callback: %s"),$thisexten['description']),
			             'edit_url' => 'config.php?display=callback&itemid='.urlencode($exten),
								  );
		}
	} else {
		return false;
	}
}

function callback_check_destinations($dest=true) {
	global $active_modules;

	$destlist = array();
	if (is_array($dest) && empty($dest)) {
		return $destlist;
	}
	$sql = "SELECT callback_id, destination, description FROM callback ";
	if ($dest !== true) {
		$sql .= "WHERE destination in ('".implode("','",$dest)."')";
	}
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	$type = isset($active_modules['callback']['type'])?$active_modules['callback']['type']:'setup';

	foreach ($results as $result) {
		$thisdest = $result['destination'];
		$thisid   = $result['callback_id'];
		$destlist[] = array(
			'dest' => $thisdest,
			'description' => sprintf(_("Callback: %s"),$result['description']),
			'edit_url' => 'config.php?display=callback&type='.$type.'&itemid='.urlencode($thisid),
		);
	}
	return $destlist;
}

/* 	Generates dialplan for callback
	We call this with retrieve_conf
*/
function callback_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	global $asterisk_conf;
	switch($engine) {
		case "asterisk":
			$timelist = callback_list();
			if(is_array($timelist)) {
				foreach($timelist as $item) {
					//$thisitem = callback_get(ltrim($item['callback_id']));
					// add dialplan
					
					// use callbacknum if avail, otherwise use cidnum
					$callback_num = (empty($item['callbacknum']) ? '${CALLERID(number)}' : $item['callbacknum']);
					$ext->add('callback', $item['callback_id'], '', new ext_setvar("CALL",$callback_num));
					
					//substitute commas with periods to keep asterisk dialplan variables happy
					$callback_destination = str_replace(",",".",$item['destination']);
					$ext->add('callback', $item['callback_id'], '', new ext_setvar("DESTINATION",$callback_destination));
					
					// set sleep time
					$sleep = (empty($item['sleep']) ? '0' : $item['sleep']);
					$ext->add('callback', $item['callback_id'], '', new ext_setvar("SLEEP",$sleep));
					
					// kick off the callback script - run in background (&) so we can hangup
					$ext->add('callback', $item['callback_id'], '', new ext_system((empty($asterisk_conf['astvarlib']) ? '/var/lib/asterisk' : $asterisk_conf['astvarlib']).'/bin/callback ${CALL} ${DESTINATION} ${SLEEP} &'));
					
					//hangup
					$ext->add('callback', $item['callback_id'], '', new ext_hangup(''));
				}
				
			}
		break;
	}
}

//get the existing meetme extensions
function callback_list() {
	$results = sql("SELECT * FROM callback","getAll",DB_FETCHMODE_ASSOC);
	if(is_array($results)){
		foreach($results as $result){
			// check to see if we have a dept match for the current AMP User.
			if (checkDept($result['deptname'])){
				// return this item's dialplan destination, and the description
				$allowed[] = $result;
			}
		}
	}
	if (isset($allowed)) {
		return $allowed;
	} else { 
		return null;
	}
}

function callback_get($id){
	//get all the variables for the meetme
	$results = sql("SELECT * FROM callback WHERE callback_id = '$id'","getRow",DB_FETCHMODE_ASSOC);
	return $results;
}

function callback_del($id){
	$results = sql("DELETE FROM callback WHERE callback_id = '$id'","query");
}

function callback_add($post){
	if(!callback_chk($post))
		return false;
	extract($post);
	if(empty($description)) $description = ${$goto0.'0'};
	$results = sql("INSERT INTO callback (description,callbacknum,destination,deptname,sleep) values (\"$description\",\"$callbacknum\",\"${$goto0.'0'}\",\"$deptname\",\"$sleep\")");
}

function callback_edit($id,$post){
	if(!callback_chk($post))
		return false;
	extract($post);
	if(empty($description)) $description = ${$goto0.'0'};
	$results = sql("UPDATE callback SET description = \"$description\", callbacknum = \"$callbacknum\", destination = \"${$goto0.'0'}\", deptname = \"$deptname\", sleep = \"$sleep\" WHERE callback_id = \"$id\"");
}

// ensures post vars is valid
function callback_chk($post){
	return true;
}
?>
