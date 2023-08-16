<?php
namespace FreePBX\modules;
use BMO;
use FreePBX_Helpers;
use PDO;
class Callback extends FreePBX_Helpers implements BMO {
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
	}

	public function install() {}
	public function uninstall() {}
	public function doConfigPageInit($page) {
		$action = $this->getReq('action','');
		$itemid = $this->getReq('itemid','');
		switch ($action) {
			case "add":
				callback_add($_POST);
				needreload();
			break;
			case "delete":
				callback_del($itemid);
				needreload();
			break;
			case "edit":
				callback_edit($itemid,$_POST);
				needreload();
			break;
		}
	}
	public function listCallbacks(){
		$sql = "SELECT * FROM callback";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$results = $stmt->fetchall(\PDO::FETCH_ASSOC);
		if(is_array($results)){
			return $results;
		}
		return [];
	}
	public function getActionBar($request) {
		$buttons = [];
		switch($request['display']) {
			case 'callback':
				$buttons = ['delete' => ['name' => 'delete', 'id' => 'delete', 'value' => _('Delete')], 'reset' => ['name' => 'reset', 'id' => 'reset', 'value' => _('Reset')], 'submit' => ['name' => 'submit', 'id' => 'submit', 'value' => _('Submit')]];
				if (empty($request['itemid'])) {
					unset($buttons['delete']);
				}
				if($request['view'] != "form"){
					$buttons = [];
				}
			break;
		}
		return $buttons;
	}
	public function chownFreePBX(){
		$webroot = \FreePBX::Config()->get('AMPWEBROOT');
		$modulebindir = $webroot . '/admin/modules/callback/bin/';
		$files = [];
		$files[] = ['type' => 'file', 'path' => $modulebindir.'callback', 'perms' => 0755];
		return $files;
	}
	public function ajaxRequest($req, &$setting) {
			 return match ($req) {
        'getJSON' => true,
        default => false,
    };
	 }
	 public function ajaxHandler(){
		return match ($_REQUEST['command']) {
      'getJSON' => match ($_REQUEST['jdata']) {
          'grid' => array_values($this->listCallbacks()),
          default => false,
      },
      default => false,
  };
	}
	public function getRightNav($request) {
		if($request['view'] == 'form'){
    	return load_view(__DIR__."/views/bootnav.php",[]);
		}
	}
	public function upsert($id,$description,$number,$destination,$sleep,$department,$timeout,$callerid){
		$sql = "INSERT INTO callback (callback_id,description,callbacknum,destination,deptname,sleep,timeout,callerid) values (:callback_id,:description,:callbacknum,:destination,:deptname,:sleep,:timeout,:callerid)";
		$sql .= " ON DUPLICATE KEY UPDATE description = VALUES(description), callbacknum= VALUES(callbacknum), destination= VALUES(destination), deptname= VALUES(deptname), sleep= VALUES(sleep), timeout= VALUES(timeout), callerid= VALUES(callerid)";
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':callback_id' => $id,
			':description' => $description,
			':callbacknum' => $number,
			':destination' => $destination,
			':deptname' => $department,
			':sleep' => $sleep,
			':timeout' => $timeout,
			':callerid' => $callerid,
		]);
	}
}
