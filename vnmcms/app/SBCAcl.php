<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SBCAcl extends Model
{
    //
  protected $table="sbc.acl";
  protected $primaryKey="i_acl";
  protected $fillable=["i_acl","ip_auth","ip_proxy","timestamp","block_regex_caller","block_regex_callee","allow_regex_caller","allow_regex_callee","description"];
  public $timestamps= false;
}
