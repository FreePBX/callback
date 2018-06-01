<?php
namespace FreePBX\modules\Callback;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
  public function runBackup($id,$transaction){
    $this->addConfigs($this->FreePBX->Callback->listCallbacks());
  }
}