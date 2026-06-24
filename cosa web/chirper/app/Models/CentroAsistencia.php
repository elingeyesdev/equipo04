<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentroAsistencia extends Model
{
    /**
     * El nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'centros_asistencia';

    /**
     * La clave primaria asociada a la tabla.
     *
     * @var string
     */
    protected $primaryKey = 'id_centro';

    /**
     * Indica si el modelo debe interactuar con los timestamps (created_at, updated_at).
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'direccion',
        'latitud',
        'longitud',
        'municipio_id',
        'hora_apertura',
        'hora_cierre',
        'contacto',
        'encargado',
        'ultima_actualizacion',
    ];

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function inventario()
    {
        return $this->hasMany(Inventario::class, 'centro_id', 'id_centro');
    }
}
