<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'truck_id',
        'health_officer_id',
        'report_status',
        'report_date',
        'remarks',
        'proof_images',
        'condition_rating',
        'signed_off',
    ];

    protected $casts = [
        'proof_images' => 'array',
        'report_date' => 'datetime',
        'signed_off' => 'boolean',
    ];

    // Relationships
    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function healthOfficer()
    {
        return $this->belongsTo(HealthOfficer::class, 'health_officer_id');
    }
}
