<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DossierMedical extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'allergies',
        'antecedents',
        'remarques',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function ficheSoins()
    {
        return $this->hasMany(FicheSoin::class);
    }
}
