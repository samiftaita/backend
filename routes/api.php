<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DentisteController;
use App\Http\Controllers\Api\DisponibiliteController;
use App\Http\Controllers\Api\DossierMedicalController;
use App\Http\Controllers\Api\FicheSoinController;
use App\Http\Controllers\Api\IntentionChatbotController;
use App\Http\Controllers\Api\RendezVousController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

// Chatbot public (sans auth)
Route::post('/chatbot/groq', [ChatbotController::class, 'ask']);

// Auth publique
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Lecture services & disponibilités — tous les utilisateurs connectés
    Route::get('/services', [ServiceController::class,      'index']);
    Route::get('/services/{id}', [ServiceController::class,      'show']);
    Route::get('/disponibilites', [DisponibiliteController::class, 'index']);
    Route::get('/disponibilites/{id}', [DisponibiliteController::class, 'show']);

    // Lecture rendez-vous — tous les utilisateurs connectés
    Route::get('/rendez-vous', [RendezVousController::class, 'index']);
    // Créneaux occupés d'une date — accessible à tous (aucune donnée personnelle)
    Route::get('/rendez-vous/occupancy', [RendezVousController::class, 'occupancy']);

    // Dossiers médicaux & fiches de soins — tous les utilisateurs connectés
    Route::get('/dossiers-medicaux', [DossierMedicalController::class, 'index']);
    Route::get('/dossiers-medicaux/{id}', [DossierMedicalController::class, 'show']);
    Route::get('/fiches-soins', [FicheSoinController::class,      'index']);
    Route::get('/fiches-soins/{id}', [FicheSoinController::class,      'show']);

    // Routes réservées à l'admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard/statistiques', [DashboardController::class, 'statistiques']);

        Route::post('/services', [ServiceController::class, 'store']);
        Route::put('/services/{id}', [ServiceController::class, 'update']);
        Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

        Route::post('/disponibilites', [DisponibiliteController::class, 'store']);
        Route::put('/disponibilites/{id}', [DisponibiliteController::class, 'update']);
        Route::delete('/disponibilites/{id}', [DisponibiliteController::class, 'destroy']);

        Route::get('/dentistes', [DentisteController::class, 'index']);
        Route::post('/dentistes', [DentisteController::class, 'store']);
        Route::get('/dentistes/{id}', [DentisteController::class, 'show']);
        Route::put('/dentistes/{id}', [DentisteController::class, 'update']);
        Route::delete('/dentistes/{id}', [DentisteController::class, 'destroy']);

        Route::get('/intentions-chatbot', [IntentionChatbotController::class, 'index']);
        Route::post('/intentions-chatbot', [IntentionChatbotController::class, 'store']);
        Route::get('/intentions-chatbot/{id}', [IntentionChatbotController::class, 'show']);
        Route::put('/intentions-chatbot/{id}', [IntentionChatbotController::class, 'update']);
        Route::delete('/intentions-chatbot/{id}', [IntentionChatbotController::class, 'destroy']);
    });

    // Création rendez-vous — patient & admin
    Route::middleware('role:patient,admin')->group(function () {
        Route::post('/rendez-vous', [RendezVousController::class, 'store']);
        Route::get('/rendez-vous/{id}', [RendezVousController::class, 'show']);
    });

    // Modification rendez-vous — admin & dentiste
    Route::middleware('role:admin,dentiste')->group(function () {
        Route::put('/rendez-vous/{id}', [RendezVousController::class, 'update']);
    });

    // Suppression rendez-vous — tous les rôles
    Route::middleware('role:patient,admin,dentiste')->group(function () {
        Route::delete('/rendez-vous/{id}', [RendezVousController::class, 'destroy']);
    });

    // Écriture dossiers médicaux & fiches de soins — admin & dentiste
    Route::middleware('role:admin,dentiste')->group(function () {
        Route::post('/dossiers-medicaux', [DossierMedicalController::class, 'store']);
        Route::put('/dossiers-medicaux/{id}', [DossierMedicalController::class, 'update']);
        Route::delete('/dossiers-medicaux/{id}', [DossierMedicalController::class, 'destroy']);

        Route::post('/fiches-soins', [FicheSoinController::class, 'store']);
        Route::put('/fiches-soins/{id}', [FicheSoinController::class, 'update']);
        Route::delete('/fiches-soins/{id}', [FicheSoinController::class, 'destroy']);
    });
});
