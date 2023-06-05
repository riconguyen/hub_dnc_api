<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChargeLog extends Model
{
    //
  protected $table="charge_log";
  protected $primaryKey="id";
  public $timestamps=false;
}
