<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $patientUser = User::factory()->create([
            'nom' => 'Utilisateur',
            'prenom' => 'Test',
            'email' => 'test@example.com',
            'telephone' => '0600000000',
            'role' => 'patient',
        ]);

        $dentisteUser = User::factory()->create([
            'nom' => 'Benali',
            'prenom' => 'Sara',
            'email' => 'dentiste@example.com',
            'telephone' => '0611111111',
            'role' => 'dentiste',
        ]);

        $patientId = DB::table('patients')->insertGetId([
            'user_id' => $patientUser->id,
            'date_naissance' => '1997-05-14',
            'adresse' => 'Casablanca',
            'sexe' => 'femme',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dentisteId = DB::table('dentistes')->insertGetId([
            'user_id' => $dentisteUser->id,
            'specialite' => 'Orthodontie',
            'numero_ordre' => 'ORD-2026-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $serviceId = DB::table('services')->insertGetId([
            'nom' => 'Consultation generale',
            'description' => 'Consultation dentaire de routine',
            'prix' => 200.00,
            'duree' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rendez_vous')->insert([
            'patient_id' => $patientId,
            'dentiste_id' => $dentisteId,
            'service_id' => $serviceId,
            'date_rdv' => now()->addDays(2)->toDateString(),
            'heure_debut' => '10:00:00',
            'heure_fin' => '10:30:00',
            'statut' => 'confirme',
            'motif' => 'Controle annuel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dossierMedicalId = DB::table('dossier_medicals')->insertGetId([
            'patient_id' => $patientId,
            'allergies' => 'Aucune',
            'antecedents' => 'Pas d\'antecedents majeurs',
            'remarques' => 'Patient cooperatif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fiche_soins')->insert([
            'dossier_medical_id' => $dossierMedicalId,
            'dentiste_id' => $dentisteId,
            'date_soin' => now()->toDateString(),
            'description' => 'Detartrage complet',
            'observation' => 'Bonne hygiene generale',
            'prix' => 300.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('disponibilites')->insert([
            'dentiste_id' => $dentisteId,
            'jour_semaine' => 'lundi',
            'heure_debut' => '09:00:00',
            'heure_fin' => '17:00:00',
            'est_disponible' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('intention_chatbots')->insert([
            'question' => 'Quels sont vos horaires ?',
            'reponse' => 'Le cabinet est ouvert du lundi au vendredi de 9h a 18h.',
            'categorie' => 'information',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
