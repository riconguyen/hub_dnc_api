<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Hotlines extends Model
{
    //
  protected $table="hot_line_config";
  protected $fillable=["cus_id","enterprise_number","hotline_number","status","sip_config","hotline_type_id","vendor_id","pause_state","init_charge","use_brand_name"];
  protected $primaryKey="id";
}
