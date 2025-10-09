<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'truck_id',
        'facility_id',
        'health_officer_id',
        'delivery_status',
        'dispatched_at',
        'delivered_at',
        'verified_at',
        'remarks',
        'proof_images',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'proof_images' => 'array',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    // Relationships
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function healthOfficer()
    {
        return $this->belongsTo(HealthOfficer::class, 'health_officer_id');
    }
}
