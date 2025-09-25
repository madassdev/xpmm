<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomingPayment extends Model
{
    protected $fillable = ['provider','provider_ref','account_number','amount','currency','payer_name','narration','value_date','status','raw'];
    protected $casts = ['raw'=>'array','value_date'=>'date'];
}
