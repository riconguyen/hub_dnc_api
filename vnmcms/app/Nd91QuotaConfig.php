<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Nd91QuotaConfig extends Model
{

  protected $table="sbc.nd91_quota_config";
  protected $fillable=["subscription_key", "max_call_per_day","max_call_per_month","config_id","name"];
  public $timestamps=false;

}
