<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ══════════════════════════════════════════
        // ADMIN
        // ══════════════════════════════════════════
        User::firstOrCreate(
            ['email' => 'admin@dentora.com'],
            [
                'nom'      => 'Admin',
                'prenom'   => 'DentOra',
                'password' => Hash::make('Admin@2026'),
                'telephone'=> '0600000000',
                'role'     => 'admin',
            ]
        );

        // ══════════════════════════════════════════
        // SERVICES
        // ══════════════════════════════════════════
        if (DB::table('services')->count() === 0) {
            $services = [
                ['nom' => 'Consultation générale',      'description' => 'Examen complet de la cavité buccale.',          'prix' => 200, 'duree' => 30],
                ['nom' => 'Détartrage',                 'description' => 'Nettoyage professionnel des dents.',             'prix' => 350, 'duree' => 45],
                ['nom' => 'Extraction dentaire',        'description' => 'Extraction d\'une ou plusieurs dents.',          'prix' => 500, 'duree' => 60],
                ['nom' => 'Pose de couronne',           'description' => 'Couronne céramique sur dent abîmée.',            'prix' => 1800,'duree' => 90],
                ['nom' => 'Blanchiment dentaire',       'description' => 'Traitement esthétique de blanchiment.',          'prix' => 1200,'duree' => 60],
                ['nom' => 'Orthodontie (bilan)',        'description' => 'Bilan orthodontique avec radiographies.',        'prix' => 400, 'duree' => 60],
                ['nom' => 'Pose d\'appareil dentaire',  'description' => 'Appareil orthodontique fixe ou amovible.',       'prix' => 8000,'duree' => 90],
                ['nom' => 'Implant dentaire',           'description' => 'Pose d\'implant en titane.',                     'prix' => 6000,'duree' => 120],
                ['nom' => 'Traitement de canal',        'description' => 'Dévitalisation et traitement endodontique.',     'prix' => 900, 'duree' => 90],
                ['nom' => 'Radiographie dentaire',      'description' => 'Panoramique ou rétro-alvéolaire.',               'prix' => 250, 'duree' => 20],
                ['nom' => 'Pose de facette',            'description' => 'Facette en céramique pour esthétique.',          'prix' => 2500,'duree' => 60],
                ['nom' => 'Prothèse amovible',          'description' => 'Dentier partiel ou complet.',                    'prix' => 3500,'duree' => 60],
            ];

            foreach ($services as $s) {
                DB::table('services')->insert(array_merge($s, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        $serviceIds = DB::table('services')->pluck('id')->toArray();

        // ══════════════════════════════════════════
        // DENTISTES
        // ══════════════════════════════════════════
        $dentistesData = [
            ['nom' => 'Benali',    'prenom' => 'Sara',    'email' => 'sara.benali@dentora.com',    'tel' => '0611111111', 'specialite' => 'Orthodontie',          'ordre' => 'ORD-2026-001'],
            ['nom' => 'Mansouri',  'prenom' => 'Karim',   'email' => 'karim.mansouri@dentora.com', 'tel' => '0622222222', 'specialite' => 'Chirurgie dentaire',    'ordre' => 'ORD-2026-002'],
            ['nom' => 'El Amrani', 'prenom' => 'Nadia',   'email' => 'nadia.elamrani@dentora.com', 'tel' => '0633333333', 'specialite' => 'Implantologie',         'ordre' => 'ORD-2026-003'],
            ['nom' => 'Tahiri',    'prenom' => 'Youssef', 'email' => 'youssef.tahiri@dentora.com', 'tel' => '0644444444', 'specialite' => 'Parodontologie',        'ordre' => 'ORD-2026-004'],
            ['nom' => 'Idrissi',   'prenom' => 'Fatima',  'email' => 'fatima.idrissi@dentora.com', 'tel' => '0655555555', 'specialite' => 'Dentisterie esthétique','ordre' => 'ORD-2026-005'],
            ['nom' => 'Berrada',   'prenom' => 'Omar',    'email' => 'omar.berrada@dentora.com',   'tel' => '0666666666', 'specialite' => 'Endodontie',            'ordre' => 'ORD-2026-006'],
        ];

        $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        $dentisteIds = [];

        foreach ($dentistesData as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'nom'      => $d['nom'],
                    'prenom'   => $d['prenom'],
                    'password' => Hash::make('Dentiste@2026'),
                    'telephone'=> $d['tel'],
                    'role'     => 'dentiste',
                ]
            );

            if ($user->wasRecentlyCreated) {
                $dentisteId = DB::table('dentistes')->insertGetId([
                    'user_id'      => $user->id,
                    'specialite'   => $d['specialite'],
                    'numero_ordre' => $d['ordre'],
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            } else {
                $dentisteId = DB::table('dentistes')->where('user_id', $user->id)->value('id');
            }

            $dentisteIds[] = $dentisteId;

            // Disponibilités (lundi à vendredi 9h-17h, samedi 9h-13h)
            $existingDispo = DB::table('disponibilites')->where('dentiste_id', $dentisteId)->count();
            if ($existingDispo === 0) {
                foreach ($jours as $jour) {
                    DB::table('disponibilites')->insert([
                        'dentiste_id'    => $dentisteId,
                        'jour_semaine'   => $jour,
                        'heure_debut'    => '09:00:00',
                        'heure_fin'      => $jour === 'samedi' ? '13:00:00' : '17:00:00',
                        'est_disponible' => true,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
            }
        }

        // ══════════════════════════════════════════
        // PATIENTS
        // ══════════════════════════════════════════
        $patientsData = [
            ['nom' => 'Alami',    'prenom' => 'Imane',   'email' => 'imane.alami@mail.com',    'tel' => '0677777777', 'naissance' => '1995-03-12', 'adresse' => 'Casablanca', 'sexe' => 'femme'],
            ['nom' => 'Cherkaoui','prenom' => 'Mehdi',   'email' => 'mehdi.cherkaoui@mail.com','tel' => '0688888888', 'naissance' => '1990-07-25', 'adresse' => 'Rabat',      'sexe' => 'homme'],
            ['nom' => 'Lahlou',   'prenom' => 'Salma',   'email' => 'salma.lahlou@mail.com',   'tel' => '0699999999', 'naissance' => '2000-01-08', 'adresse' => 'Marrakech',  'sexe' => 'femme'],
            ['nom' => 'Benkiran', 'prenom' => 'Hamza',   'email' => 'hamza.benkiran@mail.com', 'tel' => '0610101010', 'naissance' => '1988-11-30', 'adresse' => 'Fès',        'sexe' => 'homme'],
            ['nom' => 'Ziani',    'prenom' => 'Leila',   'email' => 'leila.ziani@mail.com',    'tel' => '0621212121', 'naissance' => '1997-05-14', 'adresse' => 'Tanger',     'sexe' => 'femme'],
        ];

        $patientIds = [];

        foreach ($patientsData as $p) {
            $user = User::firstOrCreate(
                ['email' => $p['email']],
                [
                    'nom'      => $p['nom'],
                    'prenom'   => $p['prenom'],
                    'password' => Hash::make('Patient@2026'),
                    'telephone'=> $p['tel'],
                    'role'     => 'patient',
                ]
            );

            if ($user->wasRecentlyCreated) {
                $patientId = DB::table('patients')->insertGetId([
                    'user_id'        => $user->id,
                    'date_naissance' => $p['naissance'],
                    'adresse'        => $p['adresse'],
                    'sexe'           => $p['sexe'],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            } else {
                $patientId = DB::table('patients')->where('user_id', $user->id)->value('id');
            }

            $patientIds[] = $patientId;
        }

        // ══════════════════════════════════════════
        // RENDEZ-VOUS
        // ══════════════════════════════════════════
        if (DB::table('rendez_vous')->count() === 0 && count($patientIds) > 0 && count($dentisteIds) > 0) {
            $statuts = ['confirme', 'en_attente', 'annule', 'confirme', 'confirme'];
            $motifs  = [
                'Contrôle annuel', 'Douleur dentaire', 'Détartrage régulier',
                'Consultation orthodontique', 'Suite traitement', 'Urgence douleur',
                'Bilan complet', 'Pose d\'appareil', 'Extraction', 'Blanchiment',
            ];

            for ($i = 0; $i < 20; $i++) {
                $patientId  = $patientIds[$i % count($patientIds)];
                $dentisteId = $dentisteIds[$i % count($dentisteIds)];
                $serviceId  = $serviceIds[$i % count($serviceIds)];
                $statut     = $statuts[$i % count($statuts)];
                $motif      = $motifs[$i % count($motifs)];
                $daysOffset = ($i % 2 === 0) ? -($i * 3) : ($i * 2);
                $heures     = [['08:00','08:30'],['09:00','09:45'],['10:00','11:00'],['11:00','11:30'],['14:00','15:00'],['15:00','16:00'],['16:00','17:00']];
                $h          = $heures[$i % count($heures)];

                DB::table('rendez_vous')->insert([
                    'patient_id'  => $patientId,
                    'dentiste_id' => $dentisteId,
                    'service_id'  => $serviceId,
                    'date_rdv'    => now()->addDays($daysOffset)->toDateString(),
                    'heure_debut' => $h[0] . ':00',
                    'heure_fin'   => $h[1] . ':00',
                    'statut'      => $statut,
                    'motif'       => $motif,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        // ══════════════════════════════════════════
        // DOSSIERS MÉDICAUX + FICHES DE SOINS
        // ══════════════════════════════════════════
        $allergies    = ['Aucune', 'Pénicilline', 'Aspirine', 'Latex', 'Ibuprofène'];
        $antecedents  = ['Aucun', 'Diabète', 'Hypertension', 'Asthme', 'Cardiopathie'];
        $descriptions = [
            'Détartrage et polissage complet',
            'Extraction dent de sagesse',
            'Pose couronne céramique sur molaire',
            'Traitement canal incisive centrale',
            'Blanchiment au peroxyde',
            'Pose implant mandibule droite',
            'Ajustement appareil orthodontique',
            'Radiographie panoramique',
        ];

        foreach ($patientIds as $idx => $patientId) {
            $existing = DB::table('dossier_medicals')->where('patient_id', $patientId)->first();

            if (!$existing) {
                $dossierId = DB::table('dossier_medicals')->insertGetId([
                    'patient_id'  => $patientId,
                    'allergies'   => $allergies[$idx % count($allergies)],
                    'antecedents' => $antecedents[$idx % count($antecedents)],
                    'remarques'   => 'Patient suivi régulièrement.',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                // 2-3 fiches de soins par patient
                $nbFiches = ($idx % 2 === 0) ? 3 : 2;
                for ($f = 0; $f < $nbFiches; $f++) {
                    $dentisteId = $dentisteIds[($idx + $f) % count($dentisteIds)];
                    DB::table('fiche_soins')->insert([
                        'dossier_medical_id' => $dossierId,
                        'dentiste_id'        => $dentisteId,
                        'date_soin'          => now()->subDays(($f + 1) * 30)->toDateString(),
                        'description'        => $descriptions[($idx + $f) % count($descriptions)],
                        'observation'        => 'Soins effectués sans complication.',
                        'prix'               => [200, 350, 500, 900, 1200, 1800][($idx + $f) % 6],
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
            }
        }

        // ══════════════════════════════════════════
        // CHATBOT INTENTIONS
        // ══════════════════════════════════════════
        if (DB::table('intention_chatbots')->count() === 0) {
            DB::table('intention_chatbots')->insert([
                ['question' => 'Quels sont vos horaires ?',           'reponse' => 'Le cabinet est ouvert du lundi au vendredi de 9h à 17h, et le samedi de 9h à 13h.', 'categorie' => 'information', 'created_at' => now(), 'updated_at' => now()],
                ['question' => 'Comment prendre un rendez-vous ?',    'reponse' => 'Vous pouvez prendre rendez-vous en ligne via notre application ou appeler le cabinet.', 'categorie' => 'rdv', 'created_at' => now(), 'updated_at' => now()],
                ['question' => 'Quels sont vos tarifs ?',             'reponse' => 'Les tarifs varient selon le soin : consultation à 200 DH, détartrage à 350 DH, extraction à partir de 500 DH.', 'categorie' => 'tarif', 'created_at' => now(), 'updated_at' => now()],
                ['question' => 'Acceptez-vous les urgences ?',        'reponse' => 'Oui, nous traitons les urgences dentaires. Appelez-nous directement pour une prise en charge rapide.', 'categorie' => 'urgence', 'created_at' => now(), 'updated_at' => now()],
                ['question' => 'Quels soins proposez-vous ?',         'reponse' => 'Nous proposons : consultation, détartrage, extraction, implants, orthodontie, blanchiment, couronnes, prothèses.', 'categorie' => 'information', 'created_at' => now(), 'updated_at' => now()],
                ['question' => 'Comment annuler un rendez-vous ?',    'reponse' => 'Vous pouvez annuler votre rendez-vous depuis votre espace patient dans l\'application.', 'categorie' => 'rdv', 'created_at' => now(), 'updated_at' => now()],
                ['question' => 'Faites-vous le blanchiment ?',        'reponse' => 'Oui, nous proposons le blanchiment dentaire professionnel à partir de 1200 DH.', 'categorie' => 'tarif', 'created_at' => now(), 'updated_at' => now()],
                ['question' => 'Combien coûte un implant dentaire ?', 'reponse' => 'Le prix d\'un implant dentaire est de 6000 DH, pose comprise.', 'categorie' => 'tarif', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
}
