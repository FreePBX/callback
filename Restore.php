<?php
namespace FreePBX\modules\Callback;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $configs = $this->getConfigs();
    foreach ($configs as $callback) {
      $this->FreePBX->Callback->upsert($callback['callback_id'],$callback['description'],$callback['callbacknum'],$callback['destination'],$callback['sleep'],$callback['deptname']);
    }
  }
  public function processLegacy($pdo, $data, $tables, $unknownTables, $tmpfiledir){
    $tables = array_flip($tables+$unknownTables);
    if(!isset($tables['callback'])){
      return $this;
    }
    $cb = $this->FreePBX->Callback;
    $cb->setDatabase($pdo);
    $data = $cb->listCallbacks();
    $cb->resetDatabase();
    foreach ($data as $callback) {
      $cb->upsert($callback['callback_id'], $callback['description'], $callback['callbacknum'], $callback['destination'], $callback['sleep'], $callback['deptname']);
    }
    return $this;
  }
}