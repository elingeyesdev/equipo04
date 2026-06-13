<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donacion extends Model
{
    use HasFactory;

    protected $table = 'donaciones';

    protected $fillable = [
        'centro_id',
        'donor_carnet',
        'items_description',
        'is_anonymous',
        'status',
        'usage_details',
        'inundacion_id',
        'victima_id',
        'photo_path',
        'edit_count',
        'edit_log',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'edit_log' => 'array',
    ];

    public function centro()
    {
        return $this->belongsTo(CentroAsistencia::class, 'centro_id', 'id_centro');
    }

    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_carnet', 'carnet');
    }

    public function inundacion()
    {
        return $this->belongsTo(Inundacion::class, 'inundacion_id');
    }

    public function victima()
    {
        return $this->belongsTo(Victima::class, 'victima_id');
    }
}
