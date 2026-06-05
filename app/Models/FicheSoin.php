<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FicheSoin extends Model
{
    use HasFactory;

    protected $fillable = [
        'dossier_medical_id',
        'dentiste_id',
        'date_soin',
        'description',
        'observation',
        'prix',
    ];

    public function dossierMedical()
    {
        return $this->belongsTo(DossierMedical::class);
    }

    public function dentiste()
    {
        return $this->belongsTo(Dentiste::class);
    }
}
