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
        // ── Admin ──────────────────────────────────────────────
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

        // ── Dentiste 1 ─────────────────────────────────────────
        $dentiste1 = User::firstOrCreate(
            ['email' => 'sara.benali@dentora.com'],
            [
                'nom'      => 'Benali',
                'prenom'   => 'Sara',
                'password' => Hash::make('Dentiste@2026'),
                'telephone'=> '0611111111',
                'role'     => 'dentiste',
            ]
        );

        if ($dentiste1->wasRecentlyCreated) {
            DB::table('dentistes')->insert([
                'user_id'      => $dentiste1->id,
                'specialite'   => 'Orthodontie',
                'numero_ordre' => 'ORD-2026-001',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // ── Dentiste 2 ─────────────────────────────────────────
        $dentiste2 = User::firstOrCreate(
            ['email' => 'karim.mansouri@dentora.com'],
            [
                'nom'      => 'Mansouri',
                'prenom'   => 'Karim',
                'password' => Hash::make('Dentiste@2026'),
                'telephone'=> '0622222222',
                'role'     => 'dentiste',
            ]
        );

        if ($dentiste2->wasRecentlyCreated) {
            DB::table('dentistes')->insert([
                'user_id'      => $dentiste2->id,
                'specialite'   => 'Chirurgie dentaire',
                'numero_ordre' => 'ORD-2026-002',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // ── Patient test ───────────────────────────────────────
        $patient = User::firstOrCreate(
            ['email' => 'patient@dentora.com'],
            [
                'nom'      => 'Utilisateur',
                'prenom'   => 'Test',
                'password' => Hash::make('Patient@2026'),
                'telephone'=> '0633333333',
                'role'     => 'patient',
            ]
        );

        if ($patient->wasRecentlyCreated) {
            $patientId = DB::table('patients')->insertGetId([
                'user_id'        => $patient->id,
                'date_naissance' => '1997-05-14',
                'adresse'        => 'Casablanca',
                'sexe'           => 'femme',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $dentisteId = DB::table('dentistes')
                ->where('user_id', $dentiste1->id)
                ->value('id');

            $serviceId = DB::table('services')->insertGetId([
                'nom'         => 'Consultation generale',
                'description' => 'Consultation dentaire de routine',
                'prix'        => 200.00,
                'duree'       => 30,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table('rendez_vous')->insert([
                'patient_id'  => $patientId,
                'dentiste_id' => $dentisteId,
                'service_id'  => $serviceId,
                'date_rdv'    => now()->addDays(2)->toDateString(),
                'heure_debut' => '10:00:00',
                'heure_fin'   => '10:30:00',
                'statut'      => 'confirme',
                'motif'       => 'Controle annuel',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $dossierMedicalId = DB::table('dossier_medicals')->insertGetId([
                'patient_id'   => $patientId,
                'allergies'    => 'Aucune',
                'antecedents'  => 'Pas d\'antecedents majeurs',
                'remarques'    => 'Patient cooperatif',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            DB::table('fiche_soins')->insert([
                'dossier_medical_id' => $dossierMedicalId,
                'dentiste_id'        => $dentisteId,
                'date_soin'          => now()->toDateString(),
                'description'        => 'Detartrage complet',
                'observation'        => 'Bonne hygiene generale',
                'prix'               => 300.00,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            DB::table('disponibilites')->insert([
                'dentiste_id'    => $dentisteId,
                'jour_semaine'   => 'lundi',
                'heure_debut'    => '09:00:00',
                'heure_fin'      => '17:00:00',
                'est_disponible' => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // ── Chatbot intentions ─────────────────────────────────
        $intentions = DB::table('intention_chatbots')->count();
        if ($intentions === 0) {
            DB::table('intention_chatbots')->insert([
                [
                    'question'   => 'Quels sont vos horaires ?',
                    'reponse'    => 'Le cabinet est ouvert du lundi au vendredi de 9h a 18h.',
                    'categorie'  => 'information',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'question'   => 'Comment prendre un rendez-vous ?',
                    'reponse'    => 'Vous pouvez prendre rendez-vous en ligne via notre application.',
                    'categorie'  => 'rdv',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
