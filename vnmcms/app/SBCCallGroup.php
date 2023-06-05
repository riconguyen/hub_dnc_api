<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SBCCallGroup extends Model
{
  protected  $fillable=['enterprise','caller','status','callee_regex','algorithm','cus_id'];
  protected $table="sbc.caller_group";
  protected $primaryKey="id";
  public $timestamps= false;
}
