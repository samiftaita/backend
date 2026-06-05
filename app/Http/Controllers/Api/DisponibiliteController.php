<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Disponibilite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DisponibiliteController extends Controller
{
    public function index()
    {
        $disponibilites = Disponibilite::with('dentiste.user')->get();

        return api_success(['disponibilites' => $disponibilites], 'Disponibilités récupérées', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dentiste_id' => 'required|exists:dentistes,id',
            'jour_semaine' => 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'est_disponible' => 'boolean',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        $disponibilite = Disponibilite::create([
            'dentiste_id' => $request->dentiste_id,
            'jour_semaine' => $request->jour_semaine,
            'heure_debut' => $request->heure_debut,
            'heure_fin' => $request->heure_fin,
            'est_disponible' => $request->est_disponible ?? true,
        ]);

        return api_success(['disponibilite' => $disponibilite], 'Créneau ajouté avec succès', 201);
    }

    public function show($id)
    {
        $disponibilite = Disponibilite::with('dentiste.user')->find($id);

        if (! $disponibilite) {
            return api_error('Disponibilité introuvable', null, 404);
        }

        return api_success(['disponibilite' => $disponibilite], 'Disponibilité récupérée', 200);
    }

    public function update(Request $request, $id)
    {
        $disponibilite = Disponibilite::find($id);

        if (! $disponibilite) {
            return api_error('Disponibilité introuvable', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'dentiste_id' => 'required|exists:dentistes,id',
            'jour_semaine' => 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'est_disponible' => 'boolean',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        $disponibilite->update([
            'dentiste_id' => $request->dentiste_id,
            'jour_semaine' => $request->jour_semaine,
            'heure_debut' => $request->heure_debut,
            'heure_fin' => $request->heure_fin,
            'est_disponible' => $request->est_disponible ?? $disponibilite->est_disponible,
        ]);

        return api_success(['disponibilite' => $disponibilite], 'Créneau modifié avec succès', 200);
    }

    public function destroy($id)
    {
        $disponibilite = Disponibilite::find($id);

        if (! $disponibilite) {
            return api_error('Disponibilité introuvable', null, 404);
        }

        $disponibilite->delete();

        return api_success(null, 'Créneau supprimé avec succès', 200);
    }
}
