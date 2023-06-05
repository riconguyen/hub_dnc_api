<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoleEntity extends Model
{
    //
  protected $table="role_entity";
  protected $fillable=['entity_id','user_id'];
  protected $primaryKey="id";
  public $timestamps=false;
}
