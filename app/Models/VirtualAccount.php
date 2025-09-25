<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualAccount extends Model
{
    protected $fillable = ['user_id','provider','account_number','bank_name','status','meta'];
    protected $casts = ['meta'=>'array'];
    public function user(){ return $this->belongsTo(\App\Models\User::class); }
}
