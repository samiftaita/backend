<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RendezVous extends Model
{
    use HasFactory;

    protected $table = 'rendez_vous';

    protected $fillable = [
        'patient_id',
        'dentiste_id',
        'service_id',
        'date_rdv',
        'heure_debut',
        'heure_fin',
        'statut',
        'motif',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentiste()
    {
        return $this->belongsTo(Dentiste::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
