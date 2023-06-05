<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceSubcriberBackup extends Model
{
protected $connection="db2";

  protected $table="service_subcriber";
  protected $primaryKey="id";
  public $timestamps= false;
}
