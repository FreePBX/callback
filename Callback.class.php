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
	public function setDatabase($database){
		$this->db = $database;
		return $this;
	}
	public function resetDatabase(){
		$this->db = $this->FreePBX->Database;
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
		return array();
	}
	public function getActionBar($request) {
		$buttons = array();
		switch($request['display']) {
			case 'callback':
				$buttons = array(
					'delete' => array(
						'name' => 'delete',
						'id' => 'delete',
						'value' => _('Delete')
					),
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
				if (empty($request['itemid'])) {
					unset($buttons['delete']);
				}
				if($request['view'] != "form"){
					$buttons = array();
				}
			break;
		}
		return $buttons;
	}
	public function chownFreePBX(){
		$webroot = \FreePBX::Config()->get('AMPWEBROOT');
		$modulebindir = $webroot . '/admin/modules/callback/bin/';
		$files = array();
		$files[] = array('type' => 'file',
						'path' => $modulebindir.'callback',
						'perms' => 0755);
		return $files;
	}
	public function ajaxRequest($req, &$setting) {
			 switch ($req) {
					 case 'getJSON':
							 return true;
					 break;
					 default:
							 return false;
					 break;
			 }
	 }
	 public function ajaxHandler(){
		switch ($_REQUEST['command']) {
			case 'getJSON':
				switch ($_REQUEST['jdata']) {
					case 'grid':
						return array_values($this->listCallbacks());
					break;

					default:
						return false;
					break;
				}
			break;

			default:
				return false;
			break;
		}
	}
	public function getRightNav($request) {
		if($request['view'] == 'form'){
    	return load_view(__DIR__."/views/bootnav.php",array());
		}
	}
	public function upsert($id,$description,$number,$destination,$sleep,$department){
		$sql = "INSERT INTO callback (callback_id,description,callbacknum,destination,deptname,sleep) values (:callback_id,:description,:callbacknum,:destination,:deptname,:sleep)";
		$sql .= " ON DUPLICATE KEY UPDATE description = VALUES(description), callbacknum= VALUES(callbacknum), destination= VALUES(destination), deptname= VALUES(deptname), sleep= VALUES(sleep)";
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':callback_id' => $id, 
			':description' => $description, 
			':callbacknum' => $number, 
			':destination' => $destination, 
			':deptname' => $department, 
			':sleep' => $sleep,
		]);
	}
}
