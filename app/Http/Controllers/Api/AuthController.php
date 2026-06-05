<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dentiste;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'telephone' => 'nullable|string|max:20',
            'role' => 'required|in:patient,dentiste,admin',

            // Champs facultatifs pour patient
            'date_naissance' => 'nullable|date',
            'adresse' => 'nullable|string|max:255',
            'sexe' => 'nullable|in:homme,femme',

            // Champs facultatifs pour dentiste
            'specialite' => 'nullable|string|max:255',
            'numero_ordre' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'telephone' => $request->telephone,
                'role' => $request->role,
            ]);

            if ($user->role === 'patient') {
                Patient::create([
                    'user_id' => $user->id,
                    'date_naissance' => $request->date_naissance,
                    'adresse' => $request->adresse,
                    'sexe' => $request->sexe,
                ]);
            }

            if ($user->role === 'dentiste') {
                Dentiste::create([
                    'user_id' => $user->id,
                    'specialite' => $request->specialite,
                    'numero_ordre' => $request->numero_ordre,
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return api_success([
                'user' => $user->load(['patient', 'dentiste']),
                'token' => $token,
            ], 'Inscription réussie', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return api_error('Une erreur est survenue lors de l\'inscription', ['exception' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return api_error('Email ou mot de passe incorrect', null, 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return api_success([
            'user' => $user->load(['patient', 'dentiste']),
            'token' => $token,
        ], 'Connexion réussie', 200);
    }

    public function profile(Request $request)
    {
        return api_success(['user' => $request->user()->load(['patient', 'dentiste'])], 'Profil utilisateur', 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return api_success(null, 'Déconnexion réussie', 200);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return api_error('Aucun compte trouvé avec cette adresse email.', null, 404);
        }

        // Générer un mot de passe temporaire lisible (12 caractères)
        $tempPassword = $this->generateReadablePassword();

        // Sauvegarder le nouveau mot de passe hashé
        $user->forceFill([
            'password' => Hash::make($tempPassword),
        ])->save();

        // Révoquer tous les tokens existants pour forcer une nouvelle connexion
        $user->tokens()->delete();

        return api_success([
            'temp_password' => $tempPassword,
        ], 'Mot de passe temporaire généré avec succès.', 200);
    }

    /**
     * Génère un mot de passe temporaire lisible de 12 caractères.
     * Format : Xxxx-0000-Xxxx (lettres + chiffres, sans caractères ambigus)
     */
    private function generateReadablePassword(): string
    {
        $letters  = 'abcdefghjkmnpqrstuvwxyz'; // sans i, l, o
        $uppers   = 'ABCDEFGHJKMNPQRSTUVWXYZ'; // sans I, L, O
        $digits   = '23456789';                 // sans 0, 1

        $part1 = strtoupper($letters[random_int(0, strlen($letters) - 1)])
               . $letters[random_int(0, strlen($letters) - 1)]
               . $letters[random_int(0, strlen($letters) - 1)]
               . $letters[random_int(0, strlen($letters) - 1)];

        $part2 = $digits[random_int(0, strlen($digits) - 1)]
               . $digits[random_int(0, strlen($digits) - 1)]
               . $digits[random_int(0, strlen($digits) - 1)]
               . $digits[random_int(0, strlen($digits) - 1)];

        $part3 = strtoupper($letters[random_int(0, strlen($letters) - 1)])
               . $letters[random_int(0, strlen($letters) - 1)]
               . $letters[random_int(0, strlen($letters) - 1)]
               . $letters[random_int(0, strlen($letters) - 1)];

        return $part1 . '-' . $part2 . '-' . $part3;
    }
}
