<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TosServices extends Model
{
    //
  protected $table="services_apps_linked";
  protected $fillable=['cus_id','service_key','enterprise_number','active'];
  protected $primaryKey="id";
}
