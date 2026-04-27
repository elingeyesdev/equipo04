<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FloodReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'citizen_carnet',
        'latitude',
        'longitude',
        'provincia',
        'municipio',
        'address',
        'description',
        'severity',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(User::class, foreignKey: 'citizen_carnet', ownerKey: 'carnet');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(AuthorityResponse::class);
    }
}
