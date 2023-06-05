<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Nd91TimeRangeConfig extends Model
{
  protected $table="sbc.nd91_time_range_config";
  protected $fillable=["name","description","active","time_allow"];
  public $timestamps=false;
}
