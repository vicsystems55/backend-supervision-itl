<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    //

        protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'country',
        'contact_person',
        'contact_phone',
        'contact_email',
        'latitude',
        'longitude',
        'description',
        'active',
    ];
}
