<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatbotSystemPrompt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    /**
     * Traiter le message utilisateur via GROQ API
     * Retourne un JSON structuré avec intent, message, action et data
     */
    public function ask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        $userMessage = trim($request->message);

        // Vérifier la configuration GROQ
        $apiKey = config('groq.api_key');
        $model = config('groq.model', 'llama-3.1-8b-instant');

        if (empty($apiKey)) {
            Log::error('GROQ_API_KEY not configured');

            return api_error('Le chatbot est momentanément indisponible (configuration manquante).', null, 500);
        }

        try {
            // Préparer les messages pour GROQ
            $systemPrompt = ChatbotSystemPrompt::generate();

            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $userMessage,
                ],
            ];

            // Appeler l'API GROQ avec format JSON obligatoire
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.3,
                'max_tokens' => 800,
                'response_format' => [
                    'type' => 'json_object',
                ],
            ]);

            if (! $response->successful()) {
                Log::error('GROQ API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'prompt' => $systemPrompt,
                ]);

                return api_error('Erreur lors de la communication avec le serveur IA: '.$response->status(), null, 500);
            }

            $data = $response->json();

            // Extraire la réponse JSON de GROQ
            if (! isset($data['choices'][0]['message']['content'])) {
                Log::warning('GROQ returned unexpected structure', ['response' => $data]);

                return api_error('Réponse inattendue du serveur IA.', null, 500);
            }

            $jsonContent = trim($data['choices'][0]['message']['content']);

            // Parser le JSON retourné par GROQ
            $chatbotResponse = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse GROQ JSON response', [
                    'content' => $jsonContent,
                    'error' => json_last_error_msg(),
                ]);

                return api_error('Format de réponse invalide du serveur IA.', null, 500);
            }

            // Handle potential wrapping by the model
            if (isset($chatbotResponse['response']) && is_array($chatbotResponse['response'])) {
                $chatbotResponse = $chatbotResponse['response'];
            }

            // Valider la structure de réponse
            if (! $this->isValidChatbotResponse($chatbotResponse)) {
                Log::warning('GROQ returned invalid response structure', ['response' => $chatbotResponse]);

                return api_error('Réponse invalide du serveur IA.', null, 500);
            }

            // Retourner la réponse formatée
            return api_success($chatbotResponse, 'Réponse du chatbot', 200);

        } catch (\Exception $e) {
            Log::error('Chatbot Exception: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return api_error('Une erreur est survenue. Veuillez réessayer.', null, 500);
        }
    }

    /**
     * Valider la structure de réponse du chatbot
     */
    private function isValidChatbotResponse($response): bool
    {
        if (! is_array($response)) {
            return false;
        }

        // Vérifier les champs obligatoires
        $requiredFields = ['intent', 'message', 'action', 'data'];
        foreach ($requiredFields as $field) {
            if (! array_key_exists($field, $response)) {
                return false;
            }
        }

        // Valider intent
        $validIntents = ['faq', 'medical_analysis', 'appointment', 'hygiene_advice', 'greeting', 'unknown'];
        if (! in_array($response['intent'], $validIntents, true)) {
            return false;
        }

        // Valider action
        if (! is_string($response['action']) || empty(trim($response['action']))) {
            return false;
        }

        // Valider message (string non vide)
        if (! is_string($response['message']) || empty(trim($response['message']))) {
            return false;
        }

        // Valider data structure
        if (! is_array($response['data'])) {
            return false;
        }

        return true;
    }
}
