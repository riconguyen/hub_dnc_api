<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivityBackup extends Model
{
    //
protected $connection="db2";
  protected $table="activity";
  protected $primaryKey="id";
  protected $fillable=['user_id','data_id','root_id','data_table','action','raw_log','description'];
}
