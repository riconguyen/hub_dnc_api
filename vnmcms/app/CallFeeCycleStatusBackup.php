<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CallFeeCycleStatusBackup extends Model
{

  protected $connection ="db2";

  protected $table="call_fee_cycle_status";
  protected $primaryKey="id";
  public $timestamps= false;
}
