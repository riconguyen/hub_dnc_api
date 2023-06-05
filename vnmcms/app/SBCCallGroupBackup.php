<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SBCCallGroupBackup extends Model
{
protected $connection="db2";
  protected  $fillable=['enterprise','caller','status','callee_regex','algorithm','cus_id'];
  protected $table="sbc.caller_group";
  protected $primaryKey="id";
  public $timestamps= false;
}
