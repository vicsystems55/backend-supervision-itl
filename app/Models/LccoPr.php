<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LccoPr extends Model
{
    use HasFactory;

    protected $fillable = [
        'installation_id',
        'lcco_name',
        'lcco_phone',
        'device_tag_code',
        'device_serial_number',
        'installation_status',
        'lcco_account_number',
        'lcco_bank_name',
        'lcco_account_name',
        'payment_status',
    ];

    public function installation()
    {
        return $this->belongsTo(Installation::class);
    }
}
