<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QuantityCycleStatusBackup extends Model
{

  protected $connection="db2";
  protected $table="quantity_subcriber_cycle_status";
  protected $primaryKey="id";
  public $timestamps= false;
}
