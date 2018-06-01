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
}