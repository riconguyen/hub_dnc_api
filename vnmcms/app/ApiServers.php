<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApiServers extends Model
{
    //
  protected $table="api_servers";
  protected $fillable=['server_name','active','ip','port','api_url', 'connection_type','server_type'];
  protected $primaryKey="id";
}
