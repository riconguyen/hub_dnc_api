<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SBCRoutingBackup extends Model
{
    protected $connection="db2";
  protected $table="sbc.routing";
  protected $primaryKey="i_routing";
  protected $fillable=["direction","caller","callee","i_acl","i_acl_backup","destination","priority","i_customer","i_vendor","description","network","i_sip_profile","status","auto_detect_blocking"];
  public $timestamps= false;
}
