<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SBCVendor extends Model
{

  protected $table="sbc.vendors";
  protected $primaryKey="i_vendor";
  public $timestamps= false;
}
