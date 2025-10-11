<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallationAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'installation_id',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function installation()
    {
        return $this->belongsTo(Installation::class);
    }
}
