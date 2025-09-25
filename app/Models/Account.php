<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['type','owner_type','owner_id','currency','status','meta'];
    protected $casts = ['meta'=>'array'];

    public function owner(){ return $this->morphTo(); }
    public function balance(){ return $this->hasOne(Balance::class); }
}
