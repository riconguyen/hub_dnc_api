<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChargeLogBackup extends Model
{

  protected $connection="db2";
  protected $table="charge_log";
  protected $primaryKey="id";
  public $timestamps=false;
}
