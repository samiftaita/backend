<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Disponibilite;
use App\Models\RendezVous;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RendezVousController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = RendezVous::with([
            'patient.user',
            'dentiste.user',
            'service',
        ]);

        // Si l'utilisateur est un patient, afficher uniquement ses rendez-vous
        if ($user && $user->role === 'patient') {
            if ($user->patient) {
                $query->where('patient_id', $user->patient->id);
            }
        }

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->has('dentiste_id')) {
            $query->where('dentiste_id', $request->dentiste_id);
        }

        if ($request->has('date_rdv')) {
            $query->where('date_rdv', $request->date_rdv);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $rendezVous = $query->orderBy('date_rdv', 'desc')
            ->orderBy('heure_debut', 'asc')
            ->get();

        return api_success(['rendez_vous' => $rendezVous], 'Rendez-vous récupérés', 200);
    }

    /**
     * Retourne les créneaux occupés pour une date donnée.
     * Ne expose que dentiste_id, heure_debut, heure_fin, statut — aucune donnée personnelle.
     * Accessible à tous les patients connectés pour calculer les prochaines heures libres.
     *
     * GET /rendez-vous/occupancy?date_rdv=YYYY-MM-DD
     */
    public function occupancy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_rdv' => 'required|date',
        ]);

        if ($validator->fails()) {
            return api_error('Paramètre date_rdv requis.', $validator->errors(), 422);
        }

        $slots = RendezVous::where('date_rdv', $request->date_rdv)
            ->where('statut', '!=', 'annule')
            ->get(['dentiste_id', 'heure_debut', 'heure_fin', 'statut']);

        return api_success(['slots' => $slots], 'Créneaux occupés récupérés', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'dentiste_id' => 'required|exists:dentistes,id',
            'service_id' => 'required|exists:services,id',
            'date_rdv' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'motif' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        // 1. Vérifier si le dentiste est disponible ce jour et à cette heure
        $jourSemaine = Carbon::parse($request->date_rdv)
            ->locale('fr')
            ->isoFormat('dddd');

        $jourSemaine = strtolower($jourSemaine);

        $disponibiliteExiste = Disponibilite::where('dentiste_id', $request->dentiste_id)
            ->where('jour_semaine', $jourSemaine)
            ->where('est_disponible', true)
            ->where('heure_debut', '<=', $request->heure_debut)
            ->where('heure_fin', '>=', $request->heure_fin)
            ->exists();

        if (! $disponibiliteExiste) {
            return api_error('Le dentiste n’est pas disponible dans ce créneau.', null, 409);
        }

        // 2. Vérifier si le créneau est déjà réservé
        $rendezVousExiste = RendezVous::where('dentiste_id', $request->dentiste_id)
            ->where('date_rdv', $request->date_rdv)
            ->where(function ($query) use ($request) {
                $query->where('heure_debut', '<', $request->heure_fin)
                    ->where('heure_fin', '>', $request->heure_debut);
            })
            ->where('statut', '!=', 'annule')
            ->exists();

        if ($rendezVousExiste) {
            return api_error('Ce créneau est déjà réservé pour ce dentiste.', null, 409);
        }

        // 3. Créer le rendez-vous
        $rendezVous = RendezVous::create([
            'patient_id' => $request->patient_id,
            'dentiste_id' => $request->dentiste_id,
            'service_id' => $request->service_id,
            'date_rdv' => $request->date_rdv,
            'heure_debut' => $request->heure_debut,
            'heure_fin' => $request->heure_fin,
            'statut' => 'en_attente',
            'motif' => $request->motif,
        ]);

        return api_success(['rendez_vous' => $rendezVous], 'Rendez-vous créé avec succès', 201);
    }

    public function show($id)
    {
        $rendezVous = RendezVous::with([
            'patient.user',
            'dentiste.user',
            'service',
        ])->find($id);

        if (! $rendezVous) {
            return api_error('Rendez-vous introuvable', null, 404);
        }

        return api_success(['rendez_vous' => $rendezVous], 'Rendez-vous récupéré', 200);
    }

    public function update(Request $request, $id)
    {
        $rendezVous = RendezVous::find($id);

        if (! $rendezVous) {
            return api_error('Rendez-vous introuvable', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'dentiste_id' => 'required|exists:dentistes,id',
            'service_id' => 'required|exists:services,id',
            'date_rdv' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'statut' => 'required|in:en_attente,confirme,annule,reporte',
            'motif' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        // 1. Vérifier si le dentiste est disponible ce jour et à cette heure
        $jourSemaine = Carbon::parse($request->date_rdv)
            ->locale('fr')
            ->isoFormat('dddd');

        $jourSemaine = strtolower($jourSemaine);

        $disponibiliteExiste = Disponibilite::where('dentiste_id', $request->dentiste_id)
            ->where('jour_semaine', $jourSemaine)
            ->where('est_disponible', true)
            ->where('heure_debut', '<=', $request->heure_debut)
            ->where('heure_fin', '>=', $request->heure_fin)
            ->exists();

        if (! $disponibiliteExiste) {
            return api_error('Le dentiste n’est pas disponible dans ce créneau.', null, 409);
        }

        // 2. Vérifier si le créneau est déjà réservé par un autre rendez-vous
        $rendezVousExiste = RendezVous::where('dentiste_id', $request->dentiste_id)
            ->where('date_rdv', $request->date_rdv)
            ->where('id', '!=', $id)
            ->where(function ($query) use ($request) {
                $query->where('heure_debut', '<', $request->heure_fin)
                    ->where('heure_fin', '>', $request->heure_debut);
            })
            ->where('statut', '!=', 'annule')
            ->exists();

        if ($rendezVousExiste) {
            return api_error('Ce créneau est déjà réservé pour ce dentiste.', null, 409);
        }

        // 3. Modifier le rendez-vous
        $rendezVous->update([
            'patient_id' => $request->patient_id,
            'dentiste_id' => $request->dentiste_id,
            'service_id' => $request->service_id,
            'date_rdv' => $request->date_rdv,
            'heure_debut' => $request->heure_debut,
            'heure_fin' => $request->heure_fin,
            'statut' => $request->statut,
            'motif' => $request->motif,
        ]);

        return api_success(['rendez_vous' => $rendezVous], 'Rendez-vous modifié avec succès', 200);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $rendezVous = RendezVous::find($id);

        if (! $rendezVous) {
            return api_error('Rendez-vous introuvable', null, 404);
        }

        // Si c'est un patient, vérifier qu'il s'agit de son propre rendez-vous et qu'il est en attente
        if ($user->role === 'patient') {
            if ($rendezVous->patient_id !== $user->patient->id) {
                return api_error('Vous ne pouvez annuler que vos propres rendez-vous', null, 403);
            }

            if ($rendezVous->statut !== 'en_attente') {
                return api_error('Vous ne pouvez annuler que les rendez-vous en attente', null, 409);
            }
        }

        // Marquer le rendez-vous comme annulé au lieu de le supprimer
        $rendezVous->update(['statut' => 'annule']);

        return api_success(['rendez_vous' => $rendezVous], 'Rendez-vous annulé avec succès', 200);
    }
}
