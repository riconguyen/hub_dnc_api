<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceConfigBackup extends Model
{
    //
  protected $connection="db2";
  protected $table="service_config";

  protected $primaryKey="id";
}
