<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'facility_id',
        'name',
        'model',
        'serial_number',
        'quantity',
        'unit',
        'status',
        'description',
    ];

    // Relationships
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
