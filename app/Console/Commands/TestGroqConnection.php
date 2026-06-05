<?php

namespace App\Console\Commands;

use App\Services\GroqService;
use Illuminate\Console\Command;

class TestGroqConnection extends Command
{
    protected $signature = 'groq:test';

    protected $description = 'Test la connexion à l\'API Groq';

    public function handle()
    {
        $this->info('Test de connexion à Groq...');

        $groqService = new GroqService;

        if ($groqService->testConnection()) {
            $this->info('[OK] Connexion à Groq réussie !');

            return 0;
        } else {
            $this->error('[ERREUR] Erreur de connexion à Groq');
            $this->line('Vérifiez:');
            $this->line('- La clé API GROQ_API_KEY dans le fichier .env');
            $this->line('- Que votre connexion Internet est active');

            return 1;
        }
    }
}
