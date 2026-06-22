<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrazabilidadInventario extends Model
{
    use HasFactory;

    protected $table = 'trazabilidad_inventario';
    public $timestamps = false; // We use fecha_actualizacion instead of created_at/updated_at

    protected $fillable = [
        'inventario_id',
        'estado_anterior',
        'estado_nuevo',
        'ubicacion_actual',
        'observacion',
        'fecha_actualizacion'
    ];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }
}
