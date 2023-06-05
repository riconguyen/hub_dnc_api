<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceOptionSubcriberBackup extends Model
{
    //
  protected $connection="db2";
  protected $table="service_option_subcriber";
  protected $primaryKey="id";
  public $timestamps=false;
}
