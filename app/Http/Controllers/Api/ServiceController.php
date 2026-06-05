<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::all();

        return api_success(['services' => $services], 'Services récupérés', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'duree' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        $service = Service::create([
            'nom' => $request->nom,
            'description' => $request->description,
            'prix' => $request->prix,
            'duree' => $request->duree,
        ]);

        return api_success(['service' => $service], 'Service ajouté avec succès', 201);
    }

    public function show($id)
    {
        $service = Service::find($id);

        if (! $service) {
            return api_error('Service introuvable', null, 404);
        }

        return api_success(['service' => $service], 'Service récupéré', 200);
    }

    public function update(Request $request, $id)
    {
        $service = Service::find($id);

        if (! $service) {
            return api_error('Service introuvable', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'duree' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return api_error('Erreur de validation', $validator->errors(), 422);
        }

        $service->update([
            'nom' => $request->nom,
            'description' => $request->description,
            'prix' => $request->prix,
            'duree' => $request->duree,
        ]);

        return api_success(['service' => $service], 'Service modifié avec succès', 200);
    }

    public function destroy($id)
    {
        $service = Service::find($id);

        if (! $service) {
            return api_error('Service introuvable', null, 404);
        }

        $service->delete();

        return api_success(null, 'Service supprimé avec succès', 200);
    }
}
