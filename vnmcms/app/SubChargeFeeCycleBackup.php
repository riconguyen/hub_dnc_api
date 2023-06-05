<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubChargeFeeCycleBackup extends Model
{

  protected $connection ="db2";
  protected $table="subcharge_fee_cycle_status";
  protected $primaryKey="id";
  public $timestamps= false;
}
