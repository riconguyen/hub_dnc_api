<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BlackListBackup extends Model
{
    //

  protected $connection="db2";
  protected $table="blacklist";
  protected $primaryKey="id";

}
