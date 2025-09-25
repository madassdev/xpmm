<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    protected $fillable = ['jref','type','currency','amount','state','meta','posted_at'];
    protected $casts = ['meta'=>'array','posted_at'=>'datetime'];
    public const PENDING='PENDING'; public const POSTED='POSTED'; public const SETTLED='SETTLED'; public const REVERSED='REVERSED';
}
