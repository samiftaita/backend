<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'telephone',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Surcharge la notification de réinitialisation de mot de passe
     * pour que le lien pointe vers le frontend React et non le backend Laravel.
     */
    public function sendPasswordResetNotification($token): void
    {
        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:3000'), '/')
            . '/reset-password'
            . '?token=' . $token
            . '&email=' . urlencode($this->email);

        $this->notify(new \App\Notifications\ResetPasswordNotification($frontendUrl));
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function dentiste()
    {
        return $this->hasOne(Dentiste::class);
    }
}
