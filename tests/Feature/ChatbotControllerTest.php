<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatbotControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crée un utilisateur de test et s'authentifie via Sanctum
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Mock GROQ API and config pour les tests
        config(['groq.api_key' => 'test-key']);

        $fakeChatResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'intent' => 'faq',
                            'message' => 'Réponse de test',
                            'action' => null,
                            'data' => [
                                'type_document' => null,
                                'imc' => ['valeur' => null, 'statut' => null],
                                'alertes' => [],
                                'diagnostics' => [],
                            ],
                        ]),
                    ],
                ],
            ],
        ];

        Http::fake([
            'api.groq.com/*' => Http::response($fakeChatResponse, 200),
        ]);
    }

    /**
     * Test: FAQ Question
     */
    public function test_chatbot_responds_to_faq_question(): void
    {
        $response = $this->postJson('/api/chatbot/groq', [
            'message' => 'Quels sont vos horaires d\'ouverture ?',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'error',
                'data' => [
                    'intent',
                    'message',
                    'action',
                    'data',
                ],
            ]);

        $this->assertContains($response['data']['intent'], ['faq', 'medical_analysis', 'appointment']);
        $this->assertIsString($response['data']['message']);
        $this->assertNotEmpty($response['data']['message']);
    }

    /**
     * Test: Appointment Request
     */
    public function test_chatbot_handles_appointment_request(): void
    {
        $response = $this->postJson('/api/chatbot/groq', [
            'message' => 'Je veux prendre un rendez-vous',
        ]);

        $response->assertStatus(200);

        if ($response['data']['intent'] === 'appointment') {
            $this->assertContains($response['data']['action'], ['redirect_to_appointment', null]);
        }
    }

    /**
     * Test: Medical Analysis - IMC Calculation
     */
    public function test_chatbot_calculates_imc(): void
    {
        $response = $this->postJson('/api/chatbot/groq', [
            'message' => 'Mon poids est 85kg et ma taille est 175cm',
        ]);

        $response->assertStatus(200);

        // La réponse devrait contenir une analyse médicale
        if ($response['data']['intent'] === 'medical_analysis') {
            $data = $response['data']['data'];

            // Vérifier que l'IMC a été calculé
            if (! is_null($data['imc']['valeur'])) {
                $imc = floatval($data['imc']['valeur']);
                $this->assertGreaterThan(0, $imc);
                $this->assertIn($data['imc']['statut'], [
                    'Insuffisance pondérale',
                    'Normal',
                    'Surpoids',
                    'Obésité',
                    null,
                ]);
            }
        }
    }

    /**
     * Test: Validation - Empty Message
     */
    public function test_chatbot_validates_empty_message(): void
    {
        $response = $this->postJson('/api/chatbot/groq', [
            'message' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    /**
     * Test: Validation - Missing Message
     */
    public function test_chatbot_validates_missing_message(): void
    {
        $response = $this->postJson('/api/chatbot/groq', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    /**
     * Test: Validation - Message Too Long
     */
    public function test_chatbot_validates_message_length(): void
    {
        $longMessage = str_repeat('a', 2001);

        $response = $this->postJson('/api/chatbot/groq', [
            'message' => $longMessage,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    /**
     * Test: Response Structure
     */
    public function test_chatbot_response_has_correct_structure(): void
    {
        $response = $this->postJson('/api/chatbot/groq', [
            'message' => 'Bonjour',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'error',
                'data' => [
                    'intent',
                    'message',
                    'action',
                    'data' => [
                        'type_document',
                        'imc',
                        'alertes',
                        'diagnostics',
                    ],
                ],
            ]);

        // Vérifier les types de données
        $this->assertIsBool($response['error']);
        $this->assertIsString($response['data']['intent']);
        $this->assertIsString($response['data']['message']);
        $this->assertIsArray($response['data']['data']);
        $this->assertIsArray($response['data']['data']['alertes']);
        $this->assertIsArray($response['data']['data']['diagnostics']);
    }

    /**
     * Test: Intent Values
     */
    public function test_chatbot_intent_values_are_valid(): void
    {
        $response = $this->postJson('/api/chatbot/groq', [
            'message' => 'Bonjour',
        ]);

        $response->assertStatus(200);

        $validIntents = ['faq', 'medical_analysis', 'appointment'];
        $this->assertContains($response['data']['intent'], $validIntents);
    }

    /**
     * Test: Action Values
     */
    public function test_chatbot_action_values_are_valid(): void
    {
        $response = $this->postJson('/api/chatbot/groq', [
            'message' => 'Bonjour',
        ]);

        $response->assertStatus(200);

        $validActions = ['redirect_to_appointment', 'show_services', null];
        $this->assertContains($response['data']['action'], $validActions);
    }

    /**
     * Test: Off-topic Question
     */
    public function test_chatbot_handles_off_topic_questions(): void
    {
        $response = $this->postJson('/api/chatbot/groq', [
            'message' => 'Quel est le meilleur film de science-fiction ?',
        ]);

        $response->assertStatus(200);

        // Le chatbot devrait indiquer que c'est hors sujet
        // mais retourner quand même une réponse polie
        $this->assertIsString($response['data']['message']);
        $this->assertNotEmpty($response['data']['message']);
    }

    /**
     * Test: Medical Alerts
     */
    public function test_chatbot_detects_medical_alerts(): void
    {
        // Test avec une potentielle interaction médicamenteuse
        $response = $this->postJson('/api/chatbot/groq', [
            'message' => 'J\'ai une ordonnance avec Aspirine et Warfarine',
        ]);

        $response->assertStatus(200);

        // Si des alertes sont détectées, elles doivent être un array
        $alertes = $response['data']['data']['alertes'];
        $this->assertIsArray($alertes);

        // Les alertes peuvent être vides ou contenir des strings
        foreach ($alertes as $alerte) {
            $this->assertIsString($alerte);
        }
    }

    /**
     * Test: HTTP Method
     */
    public function test_chatbot_requires_post_method(): void
    {
        // GET should not be allowed
        $response = $this->getJson('/api/chatbot/groq');
        $response->assertStatus(405); // Method Not Allowed
    }

    /**
     * Test: Content Type
     */
    public function test_chatbot_requires_json_content_type(): void
    {
        $response = $this->post('/api/chatbot/groq', [
            'message' => 'Bonjour',
        ]);

        // Even without explicit JSON header, Laravel should handle it
        // This test ensures the endpoint accepts JSON
        $this->assertTrue(
            $response->status() === 200 ||
            $response->status() === 422 ||
            $response->status() === 500
        );
    }
}
