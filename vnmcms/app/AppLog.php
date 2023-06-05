<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppLog extends Model
{
    //

  protected $table="app_log";
  protected $primaryKey="id";
  public $timestamps=false;

}
