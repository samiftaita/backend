<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dentiste extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialite',
        'numero_ordre',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rendezVous()
    {
        return $this->hasMany(RendezVous::class);
    }

    public function ficheSoins()
    {
        return $this->hasMany(FicheSoin::class);
    }

    public function disponibilites()
    {
        return $this->hasMany(Disponibilite::class);
    }
}
