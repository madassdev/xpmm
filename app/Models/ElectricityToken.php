<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectricityToken extends Model
{
    protected $fillable = ['bill_transaction_id','token','units','tariff_code','raw'];
    protected $casts = ['raw'=>'array'];

    public function transaction() { return $this->belongsTo(\App\Models\BillTransaction::class); }
}
