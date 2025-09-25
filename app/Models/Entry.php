<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    protected $fillable = ['journal_id','account_id','direction','amount'];
    public const DEBIT='DEBIT'; public const CREDIT='CREDIT';
}
