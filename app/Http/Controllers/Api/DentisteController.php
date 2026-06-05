<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dentiste;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DentisteController extends Controller
{
    public function index()
    {
        $dentistes = Dentiste::with('user')->get();

        return api_success(['dentistes' => $dentistes], 'Dentistes récupérés', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'specialite' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'prenom' => $request->prenom,
                'nom' => $request->nom,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'dentiste',
            ]);

            $dentiste = Dentiste::create([
                'user_id' => $user->id,
                'specialite' => $request->specialite,
                'telephone' => $request->telephone,
            ]);

            DB::commit();

            return api_success(['dentiste' => $dentiste->load('user')], 'Dentiste ajouté avec succès', 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return api_error('Erreur lors de la création du dentiste', ['exception' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $dentiste = Dentiste::with('user')->find($id);

        if (! $dentiste) {
            return api_error('Dentiste introuvable', null, 404);
        }

        return api_success(['dentiste' => $dentiste], 'Dentiste récupéré', 200);
    }

    public function update(Request $request, $id)
    {
        $dentiste = Dentiste::find($id);

        if (! $dentiste) {
            return api_error('Dentiste introuvable', null, 404);
        }

        $user = $dentiste->user;

        $validator = Validator::make($request->all(), [
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'specialite' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        try {
            DB::beginTransaction();

            $user->update([
                'prenom' => $request->prenom,
                'nom' => $request->nom,
                'email' => $request->email,
            ]);

            if ($request->filled('password')) {
                $user->update(['password' => Hash::make($request->password)]);
            }

            $dentiste->update([
                'specialite' => $request->specialite,
                'telephone' => $request->telephone,
            ]);

            DB::commit();

            return api_success(['dentiste' => $dentiste->load('user')], 'Dentiste modifié avec succès', 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return api_error('Erreur lors de la modification', ['exception' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $dentiste = Dentiste::find($id);

        if (! $dentiste) {
            return api_error('Dentiste introuvable', null, 404);
        }

        try {
            DB::beginTransaction();

            $user = $dentiste->user;
            $dentiste->delete();
            $user->delete();

            DB::commit();

            return api_success(null, 'Dentiste supprimé avec succès', 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return api_error('Erreur lors de la suppression', ['exception' => $e->getMessage()], 500);
        }
    }
}
