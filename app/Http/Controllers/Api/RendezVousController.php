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
    /**
     * Map ISO weekday number to French day name stored in DB.
     * Carbon isoWeekday(): 1=Monday ... 7=Sunday
     */
    private function getJourSemaine(string $date): string
    {
        $map = [
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
            7 => 'dimanche',
        ];
        return $map[Carbon::parse($date)->isoWeekday()];
    }

    public function index(Request $request)
    {
        $user  = Auth::user();
        $query = RendezVous::with(['patient.user', 'dentiste.user', 'service']);

        if ($user && $user->role === 'patient' && $user->patient) {
            $query->where('patient_id', $user->patient->id);
        }

        if ($request->has('patient_id'))  $query->where('patient_id',  $request->patient_id);
        if ($request->has('dentiste_id')) $query->where('dentiste_id', $request->dentiste_id);
        if ($request->has('date_rdv'))    $query->where('date_rdv',    $request->date_rdv);
        if ($request->has('statut'))      $query->where('statut',      $request->statut);

        $rendezVous = $query->orderBy('date_rdv', 'desc')
            ->orderBy('heure_debut', 'asc')
            ->get();

        return api_success(['rendez_vous' => $rendezVous], 'Rendez-vous recuperes', 200);
    }

    /**
     * GET /rendez-vous/occupancy?date_rdv=YYYY-MM-DD
     */
    public function occupancy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_rdv' => 'required|date',
        ]);

        if ($validator->fails()) {
            return api_error('Parametre date_rdv requis.', $validator->errors(), 422);
        }

        $slots = RendezVous::where('date_rdv', $request->date_rdv)
            ->where('statut', '!=', 'annule')
            ->get(['dentiste_id', 'heure_debut', 'heure_fin', 'statut']);

        return api_success(['slots' => $slots], 'Creneaux occupes recuperes', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id'  => 'required|exists:patients,id',
            'dentiste_id' => 'required|exists:dentistes,id',
            'service_id'  => 'required|exists:services,id',
            'date_rdv'    => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin'   => 'required|date_format:H:i|after:heure_debut',
            'motif'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        // 1. Verifier disponibilite du dentiste
        $jourSemaine = $this->getJourSemaine($request->date_rdv);

        $heureDebut = $request->heure_debut . (strlen($request->heure_debut) === 5 ? ':00' : '');
        $heureFin   = $request->heure_fin   . (strlen($request->heure_fin)   === 5 ? ':00' : '');

        $disponibiliteExiste = Disponibilite::where('dentiste_id', $request->dentiste_id)
            ->where('jour_semaine', $jourSemaine)
            ->where('est_disponible', true)
            ->where('heure_debut', '<=', $heureDebut)
            ->where('heure_fin', '>=', $heureFin)
            ->exists();

        if (!$disponibiliteExiste) {
            return api_error('Le dentiste n\'est pas disponible dans ce creneau.', null, 409);
        }

        // 2. Verifier si le creneau est deja reserve
        $rendezVousExiste = RendezVous::where('dentiste_id', $request->dentiste_id)
            ->where('date_rdv', $request->date_rdv)
            ->where(function ($query) use ($heureDebut, $heureFin) {
                $query->where('heure_debut', '<', $heureFin)
                    ->where('heure_fin', '>', $heureDebut);
            })
            ->where('statut', '!=', 'annule')
            ->exists();

        if ($rendezVousExiste) {
            return api_error('Ce creneau est deja reserve pour ce dentiste.', null, 409);
        }

        // 3. Creer le rendez-vous
        $rendezVous = RendezVous::create([
            'patient_id'  => $request->patient_id,
            'dentiste_id' => $request->dentiste_id,
            'service_id'  => $request->service_id,
            'date_rdv'    => $request->date_rdv,
            'heure_debut' => $request->heure_debut,
            'heure_fin'   => $request->heure_fin,
            'statut'      => 'en_attente',
            'motif'       => $request->motif,
        ]);

        return api_success(['rendez_vous' => $rendezVous], 'Rendez-vous cree avec succes', 201);
    }

    public function show($id)
    {
        $rendezVous = RendezVous::with(['patient.user', 'dentiste.user', 'service'])->find($id);

        if (!$rendezVous) {
            return api_error('Rendez-vous introuvable', null, 404);
        }

        return api_success(['rendez_vous' => $rendezVous], 'Rendez-vous recupere', 200);
    }

    public function update(Request $request, $id)
    {
        $rendezVous = RendezVous::find($id);

        if (!$rendezVous) {
            return api_error('Rendez-vous introuvable', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'patient_id'  => 'required|exists:patients,id',
            'dentiste_id' => 'required|exists:dentistes,id',
            'service_id'  => 'required|exists:services,id',
            'date_rdv'    => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin'   => 'required|date_format:H:i|after:heure_debut',
            'statut'      => 'required|in:en_attente,confirme,annule,reporte',
            'motif'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        // 1. Verifier disponibilite du dentiste
        $jourSemaine = $this->getJourSemaine($request->date_rdv);

        $heureDebut = $request->heure_debut . (strlen($request->heure_debut) === 5 ? ':00' : '');
        $heureFin   = $request->heure_fin   . (strlen($request->heure_fin)   === 5 ? ':00' : '');

        $disponibiliteExiste = Disponibilite::where('dentiste_id', $request->dentiste_id)
            ->where('jour_semaine', $jourSemaine)
            ->where('est_disponible', true)
            ->where('heure_debut', '<=', $heureDebut)
            ->where('heure_fin', '>=', $heureFin)
            ->exists();

        if (!$disponibiliteExiste) {
            return api_error('Le dentiste n\'est pas disponible dans ce creneau.', null, 409);
        }

        // 2. Verifier si le creneau est deja reserve par un autre RDV
        $rendezVousExiste = RendezVous::where('dentiste_id', $request->dentiste_id)
            ->where('date_rdv', $request->date_rdv)
            ->where('id', '!=', $id)
            ->where(function ($query) use ($heureDebut, $heureFin) {
                $query->where('heure_debut', '<', $heureFin)
                    ->where('heure_fin', '>', $heureDebut);
            })
            ->where('statut', '!=', 'annule')
            ->exists();

        if ($rendezVousExiste) {
            return api_error('Ce creneau est deja reserve pour ce dentiste.', null, 409);
        }

        // 3. Modifier le rendez-vous
        $rendezVous->update([
            'patient_id'  => $request->patient_id,
            'dentiste_id' => $request->dentiste_id,
            'service_id'  => $request->service_id,
            'date_rdv'    => $request->date_rdv,
            'heure_debut' => $request->heure_debut,
            'heure_fin'   => $request->heure_fin,
            'statut'      => $request->statut,
            'motif'       => $request->motif,
        ]);

        return api_success(['rendez_vous' => $rendezVous], 'Rendez-vous modifie avec succes', 200);
    }

    public function destroy($id)
    {
        $user       = Auth::user();
        $rendezVous = RendezVous::find($id);

        if (!$rendezVous) {
            return api_error('Rendez-vous introuvable', null, 404);
        }

        if ($user->role === 'patient') {
            if ($rendezVous->patient_id !== $user->patient->id) {
                return api_error('Vous ne pouvez annuler que vos propres rendez-vous', null, 403);
            }
            if ($rendezVous->statut !== 'en_attente') {
                return api_error('Vous ne pouvez annuler que les rendez-vous en attente', null, 409);
            }
        }

        $rendezVous->update(['statut' => 'annule']);

        return api_success(['rendez_vous' => $rendezVous], 'Rendez-vous annule avec succes', 200);
    }
}
