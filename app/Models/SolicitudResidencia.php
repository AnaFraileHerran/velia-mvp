<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Solicitud de alta de otra residencia desde «Mi residencia». La atiende ICARUS; sirve también para medir la demanda de varios centros. */
class SolicitudResidencia extends Model
{
    protected $table = 'solicitudes_residencia';

    protected $fillable = ['residencia_id', 'user_id', 'nombre', 'plazas', 'municipio', 'estado'];
}
