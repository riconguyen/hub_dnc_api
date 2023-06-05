<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Nd91DncConfig extends Model
{

  protected $table="sbc.nd91_config";
  protected $fillable=["active", "config_key","apply_rule"];
}
