<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    use HasFactory;

    protected $table = 'inventario';

    protected $fillable = [
        'centro_id',
        'donor_carnet',
        'categoria',
        'descripcion',
        'is_anonymous',
        'status',
        'usage_details',
        'inundacion_id',
        'victima_id',
        'photo_path'
    ];

    public function centro()
    {
        return $this->belongsTo(CentroAsistencia::class, 'centro_id', 'id_centro');
    }

    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_carnet', 'carnet');
    }

    public function trazabilidad()
    {
        return $this->hasMany(TrazabilidadInventario::class, 'inventario_id');
    }
}
