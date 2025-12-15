<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installation extends Model
{
    //
    protected $guarded = [];

        // Make sure all these relationships exist
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function healthOfficer()
    {
        return $this->belongsTo(HealthOfficer::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    public function lccoPrs(): HasMany
    {
        return $this->hasMany(LccoPr::class);
    }

}
