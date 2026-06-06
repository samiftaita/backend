<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dentiste;
use App\Models\Patient;
use App\Models\RendezVous;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function statistiques()
    {
        try {
        // ── Compteurs globaux ──────────────────────────────────────────
        $totalRdv = RendezVous::count();
        $enAttente = RendezVous::where('statut', 'en_attente')->count();
        $confirmes = RendezVous::where('statut', 'confirme')->count();
        $annules = RendezVous::where('statut', 'annule')->count();
        $reportes = RendezVous::where('statut', 'reporte')->count();

        // ── RDV des 6 derniers mois (pour courbe) ──────────────────────
        $moisLabels = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
        $rdvParMois = [];
        for ($i = 5; $i >= 0; $i--) {
            $date  = Carbon::now()->subMonths($i);
            $label = $moisLabels[$date->month - 1] . ' ' . $date->year;
            $count = RendezVous::whereYear('date_rdv', $date->year)
                ->whereMonth('date_rdv', $date->month)
                ->count();
            $rdvParMois[] = ['mois' => $label, 'total' => $count];
        }

        // ── RDV par statut (pour donut) ────────────────────────────────
        $rdvParStatut = [
            ['statut' => 'Confirmés',  'total' => $confirmes, 'color' => '#10b981'],
            ['statut' => 'En attente', 'total' => $enAttente, 'color' => '#f59e0b'],
            ['statut' => 'Annulés',    'total' => $annules,   'color' => '#ef4444'],
            ['statut' => 'Reportés',   'total' => $reportes,  'color' => '#0ea5e9'],
        ];

        // ── RDV par jour de la semaine (pour bar chart) ────────────────
        $joursLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $rdvParJour  = [];

        $driver = config('database.default');

        if ($driver === 'mysql') {
            $rawJours = RendezVous::select(
                DB::raw('DAYOFWEEK(date_rdv) as jour'),
                DB::raw('COUNT(*) as total')
            )
                ->groupBy('jour')
                ->pluck('total', 'jour')
                ->toArray();
            // DAYOFWEEK: 1=Dim, 2=Lun, ..., 7=Sam
            $mapping = [2 => 0, 3 => 1, 4 => 2, 5 => 3, 6 => 4, 7 => 5, 1 => 6];
            foreach ($joursLabels as $idx => $label) {
                $dbKey = array_search($idx, $mapping);
                $rdvParJour[] = ['jour' => $label, 'total' => $rawJours[$dbKey] ?? 0];
            }
        } else {
            // SQLite : strftime('%w') → 0=Dim, 1=Lun, ..., 6=Sam
            $rawJours = RendezVous::select(
                DB::raw("strftime('%w', date_rdv) as jour"),
                DB::raw('COUNT(*) as total')
            )
                ->groupBy('jour')
                ->pluck('total', 'jour')
                ->toArray();
            $mapping = ['1' => 0, '2' => 1, '3' => 2, '4' => 3, '5' => 4, '6' => 5, '0' => 6];
            foreach ($joursLabels as $idx => $label) {
                $dbKey = array_search($idx, $mapping);
                $rdvParJour[] = ['jour' => $label, 'total' => $rawJours[$dbKey] ?? 0];
            }
        }

        // ── Derniers rendez-vous (tableau récent) ──────────────────────
        $derniersRdv = RendezVous::with(['patient.user', 'dentiste.user', 'service'])
            ->orderByDesc('date_rdv')
            ->orderByDesc('heure_debut')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'patient' => trim(($r->patient->user->prenom ?? '').' '.($r->patient->user->nom ?? '')),
                'dentiste' => 'Dr. '.trim(($r->dentiste->user->prenom ?? '').' '.($r->dentiste->user->nom ?? '')),
                'service' => $r->service->nom ?? '—',
                'date_rdv' => $r->date_rdv,
                'heure_debut' => substr($r->heure_debut, 0, 5),
                'statut' => $r->statut,
            ]);

        // ── Top services (bar horizontal) ─────────────────────────────
        $topServices = RendezVous::select('service_id', DB::raw('COUNT(*) as total'))
            ->with('service')
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'service' => $r->service->nom ?? 'Inconnu',
                'total' => $r->total,
            ]);

        $data = [
            // Compteurs
            'nombre_patients' => Patient::count(),
            'nombre_dentistes' => Dentiste::count(),
            'nombre_services' => Service::count(),
            'nombre_rendez_vous' => $totalRdv,
            'rendez_vous_en_attente' => $enAttente,
            'rendez_vous_confirmes' => $confirmes,
            'rendez_vous_annules' => $annules,
            'rendez_vous_reportes' => $reportes,
            // Graphiques
            'rdv_par_mois' => $rdvParMois,
            'rdv_par_statut' => $rdvParStatut,
            'rdv_par_jour' => $rdvParJour,
            'derniers_rdv' => $derniersRdv,
            'top_services' => $topServices,
        ];

        return api_success($data, 'Statistiques récupérées', 200);
        } catch (\Exception $e) {
            Log::error('Dashboard statistiques error: ' . $e->getMessage());
            return api_error('Erreur lors du chargement des statistiques: ' . $e->getMessage(), null, 500);
        }
    }
}
