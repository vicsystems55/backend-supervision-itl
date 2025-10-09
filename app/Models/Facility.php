<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'lga_id',
        'name',
        'address',
        'facility_type',
        'supply_chain_level',
        'road_accessible',
        'distance_from_hub_km',
        'road_quality'
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function lga()
    {
        return $this->belongsTo(Lga::class);
    }

    public function installations()
    {
        return $this->hasMany(Installation::class);
    }

    
}
