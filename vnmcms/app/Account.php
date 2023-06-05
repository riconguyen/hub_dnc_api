<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    //
    protected $table = 'tblAccounts';
    protected $fillable = ['full_name', 'phone','title'];
}
