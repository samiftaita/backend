<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DossierMedical;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DossierMedicalController extends Controller
{
    public function index()
    {
        $dossiers = DossierMedical::with('patient.user')->get();

        return api_success(['dossiers_medicaux' => $dossiers], 'Dossiers médicaux récupérés', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id|unique:dossier_medicals,patient_id',
            'allergies' => 'nullable|string',
            'antecedents' => 'nullable|string',
            'remarques' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        $dossier = DossierMedical::create([
            'patient_id' => $request->patient_id,
            'allergies' => $request->allergies,
            'antecedents' => $request->antecedents,
            'remarques' => $request->remarques,
        ]);

        return api_success(['dossier_medical' => $dossier], 'Dossier médical créé avec succès', 201);
    }

    public function show($id)
    {
        $dossier = DossierMedical::with('patient.user', 'ficheSoins')->find($id);

        if (! $dossier) {
            return api_error('Dossier médical introuvable', null, 404);
        }

        return api_success(['dossier_medical' => $dossier], 'Dossier médical récupéré', 200);
    }

    public function update(Request $request, $id)
    {
        $dossier = DossierMedical::find($id);

        if (! $dossier) {
            return api_error('Dossier médical introuvable', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id|unique:dossier_medicals,patient_id,'.$id,
            'allergies' => 'nullable|string',
            'antecedents' => 'nullable|string',
            'remarques' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        $dossier->update([
            'patient_id' => $request->patient_id,
            'allergies' => $request->allergies,
            'antecedents' => $request->antecedents,
            'remarques' => $request->remarques,
        ]);

        return api_success(['dossier_medical' => $dossier], 'Dossier médical modifié avec succès', 200);
    }

    public function destroy($id)
    {
        $dossier = DossierMedical::find($id);

        if (! $dossier) {
            return api_error('Dossier médical introuvable', null, 404);
        }

        $dossier->delete();

        return api_success(null, 'Dossier médical supprimé avec succès', 200);
    }
}
