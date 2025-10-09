<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TruckRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'truck_id',
        'delivery_id',
        'latitude',
        'longitude',
        'recorded_at',
        'speed',
        'fuel_level',
        'status',
        'meta',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'meta' => 'array',
    ];

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }
}
