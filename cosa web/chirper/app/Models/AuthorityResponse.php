<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorityResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'flood_report_id',
        'authority_carnet',
        'message',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(FloodReport::class, foreignKey: 'flood_report_id');
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(User::class, foreignKey: 'authority_carnet', ownerKey: 'carnet');
    }
}
