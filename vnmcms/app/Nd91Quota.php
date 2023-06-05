<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Nd91Quota extends Model
{

  protected $table="sbc.nd91_quota";
  protected $fillable=["active", "config_key","apply_rule"];

}
