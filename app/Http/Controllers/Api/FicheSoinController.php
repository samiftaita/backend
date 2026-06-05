<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FicheSoin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FicheSoinController extends Controller
{
    public function index()
    {
        $fiches = FicheSoin::with([
            'dossierMedical.patient.user',
            'dentiste.user',
        ])->get();

        return api_success(['fiches_soins' => $fiches], 'Fiches de soins récupérées', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dossier_medical_id' => 'required|exists:dossier_medicals,id',
            'dentiste_id' => 'required|exists:dentistes,id',
            'date_soin' => 'required|date',
            'description' => 'required|string',
            'observation' => 'nullable|string',
            'prix' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        $fiche = FicheSoin::create([
            'dossier_medical_id' => $request->dossier_medical_id,
            'dentiste_id' => $request->dentiste_id,
            'date_soin' => $request->date_soin,
            'description' => $request->description,
            'observation' => $request->observation,
            'prix' => $request->prix,
        ]);

        return api_success(['fiche_soin' => $fiche], 'Fiche de soin créée avec succès', 201);
    }

    public function show($id)
    {
        $fiche = FicheSoin::with([
            'dossierMedical.patient.user',
            'dentiste.user',
        ])->find($id);

        if (! $fiche) {
            return api_error('Fiche de soin introuvable', null, 404);
        }

        return api_success(['fiche_soin' => $fiche], 'Fiche de soin récupérée', 200);
    }

    public function update(Request $request, $id)
    {
        $fiche = FicheSoin::find($id);

        if (! $fiche) {
            return api_error('Fiche de soin introuvable', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'dossier_medical_id' => 'required|exists:dossier_medicals,id',
            'dentiste_id' => 'required|exists:dentistes,id',
            'date_soin' => 'required|date',
            'description' => 'required|string',
            'observation' => 'nullable|string',
            'prix' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        $fiche->update([
            'dossier_medical_id' => $request->dossier_medical_id,
            'dentiste_id' => $request->dentiste_id,
            'date_soin' => $request->date_soin,
            'description' => $request->description,
            'observation' => $request->observation,
            'prix' => $request->prix,
        ]);

        return api_success(['fiche_soin' => $fiche], 'Fiche de soin modifiée avec succès', 200);
    }

    public function destroy($id)
    {
        $fiche = FicheSoin::find($id);

        if (! $fiche) {
            return api_error('Fiche de soin introuvable', null, 404);
        }

        $fiche->delete();

        return api_success(null, 'Fiche de soin supprimée avec succès', 200);
    }
}
