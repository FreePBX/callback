<?php /* $Id */

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
