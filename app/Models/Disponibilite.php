<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disponibilite extends Model
{
    use HasFactory;

    protected $fillable = [
        'dentiste_id',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
        'est_disponible',
    ];

    public function dentiste()
    {
        return $this->belongsTo(Dentiste::class);
    }
}
