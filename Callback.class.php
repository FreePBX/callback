<?php
namespace FreePBX\modules;

class Callback implements \BMO {
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
	}
    public function install() {}
    public function uninstall() {}
    public function backup() {}
    public function restore($backup) {}
    public function doConfigPageInit($page) {
    	isset($_REQUEST['action'])?$action = $_REQUEST['action']:$action='';
		isset($_REQUEST['itemid'])?$itemid=$_REQUEST['itemid']:$itemid='';
		switch ($action) {
			case "add":
				$_REQUEST['itemid'] = callback_add($_POST);
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
			case "getJSON":
			    header('Content-Type: application/json');    
			    switch ($_REQUEST['jdata']) { 
			    	case 'grid':
			    		$data = callback_list();
			    		echo json_encode($data);
			    		exit();
			    	break;
			    	default:
			    		json_encode(array("Error"=>_("Invalid Request")));
			    		exit();
			    	break;
			    }
			    break;
		}
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
}
