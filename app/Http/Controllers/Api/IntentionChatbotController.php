<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntentionChatbot;
use App\Services\GroqService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IntentionChatbotController extends Controller
{
    public function index()
    {
        $intentions = IntentionChatbot::all();

        return response()->json([
            'intentions_chatbot' => $intentions,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string',
            'reponse' => 'required|string',
            'categorie' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $intention = IntentionChatbot::create([
            'question' => $request->question,
            'reponse' => $request->reponse,
            'categorie' => $request->categorie,
        ]);

        return response()->json([
            'message' => 'Intention chatbot ajoutée avec succès',
            'intention_chatbot' => $intention,
        ], 201);
    }

    public function show($id)
    {
        $intention = IntentionChatbot::find($id);

        if (! $intention) {
            return response()->json([
                'message' => 'Intention chatbot introuvable',
            ], 404);
        }

        return response()->json([
            'intention_chatbot' => $intention,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $intention = IntentionChatbot::find($id);

        if (! $intention) {
            return response()->json([
                'message' => 'Intention chatbot introuvable',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'question' => 'required|string',
            'reponse' => 'required|string',
            'categorie' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $intention->update([
            'question' => $request->question,
            'reponse' => $request->reponse,
            'categorie' => $request->categorie,
        ]);

        return response()->json([
            'message' => 'Intention chatbot modifiée avec succès',
            'intention_chatbot' => $intention,
        ], 200);
    }

    public function destroy($id)
    {
        $intention = IntentionChatbot::find($id);

        if (! $intention) {
            return response()->json([
                'message' => 'Intention chatbot introuvable',
            ], 404);
        }

        $intention->delete();

        return response()->json([
            'message' => 'Intention chatbot supprimée avec succès',
        ], 200);
    }

    public function chercherReponse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $groqService = new GroqService;
        $reponse = $groqService->chat($request->message);

        return response()->json([
            'reponse' => $reponse,
            'source' => 'groq',
        ], 200);
    }
}
