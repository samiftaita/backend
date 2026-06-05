<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GroqService
{
    protected $apiKey;

    protected $model;

    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('groq.api_key');
        $this->model = config('groq.model');
        $this->baseUrl = config('groq.base_url');
    }

    /**
     * Envoyer un message à Groq et obtenir une réponse
     */
    public function chat(string $message, ?array $context = null): string
    {
        try {
            $messages = $this->buildMessages($message, $context);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['choices'][0]['message']['content'] ?? 'Aucune réponse reçue';
            }

            \Log::error('Groq API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return 'Erreur lors de la communication avec le serveur';
        } catch (\Exception $e) {
            \Log::error('Groq Service Error: '.$e->getMessage());

            return 'Erreur : '.$e->getMessage();
        }
    }

    /**
     * Construire les messages pour l'API
     */
    protected function buildMessages(string $userMessage, ?array $context = null): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt(),
            ],
        ];

        // Ajouter le contexte si fourni
        if ($context) {
            foreach ($context as $contextItem) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $contextItem,
                ];
            }
        }

        // Ajouter le message utilisateur
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        return $messages;
    }

    /**
     * Prompt système pour le chatbot dentaire
     */
    protected function getSystemPrompt(): string
    {
        return "Tu es un assistant chatbot pour un cabinet dentaire. Tu dois être courtois, professionnel et utile. 
Tu dois aider les patients avec des informations sur les services dentaires, la prise de rendez-vous, et les conseils généraux sur la santé dentaire.
Si le patient pose une question qui ne concerne pas l'odontologie, réponds poliment que tu ne peux pas aider avec ça.
Propose toujours aux patients de prendre rendez-vous si nécessaire.
Sois concis dans tes réponses (maximum 2-3 phrases).";
    }

    /**
     * Vérifier la connexion à l'API Groq
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test',
                    ],
                ],
                'max_tokens' => 5,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            \Log::error('Groq Connection Test Failed: '.$e->getMessage());

            return false;
        }
    }
}
