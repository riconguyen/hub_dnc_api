<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TosServicesBackup extends Model
{
    //
  protected $connection="db2";
  protected $table="services_apps_linked";
  protected $fillable=['cus_id','service_key','enterprise_number','active'];
  protected $primaryKey="id";
}
