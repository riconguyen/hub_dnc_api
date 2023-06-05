<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
  protected $table="entity";
  protected $fillable=["entity_key", "entity_name","entity_group"];
  protected $primaryKey="id";
  public $timestamps=false;
}
