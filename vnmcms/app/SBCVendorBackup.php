<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SBCVendorBackup extends Model
{
  protected $connection="db2";
  protected $table="sbc.vendors";
  protected $primaryKey="i_vendor";
  public $timestamps= false;
}
