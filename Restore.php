<?php
namespace FreePBX\modules\Callback;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore(){
		$configs = $this->getConfigs();
		foreach ($configs as $callback) {
			$this->FreePBX->Callback->upsert($callback['callback_id'],$callback['description'],$callback['callbacknum'],$callback['destination'],$callback['sleep'],$callback['deptname']);
		}
	}
	public function processLegacy($pdo, $data, $tables, $unknownTables){
		$tables = ['callback'];
		foreach($tables as $table) {
			$sth = $pdo->query("SELECT * FROM $table",\PDO::FETCH_ASSOC);
			$res = $sth->fetchAll();
			$this->addDataToTableFromArray($table, $res);
		}
	}
}
