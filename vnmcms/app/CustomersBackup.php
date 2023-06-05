<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomersBackup extends Model
{
    //
 protected $connection="db2";
  protected $table="customers";
  protected $primaryKey="id";
}
