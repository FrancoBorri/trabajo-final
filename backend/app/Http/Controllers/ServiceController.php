<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return Service::with('professional.user')->get();
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'professional_id' => 'sometimes|integer|exists:professionals,id',
        ]);


        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // If professional_id provided, verify authorization (owner or admin)
        if (isset($data['professional_id'])) {
            $professional = Professional::findOrFail($data['professional_id']);
            if ($professional->user_id !== $user->id && (($user->role ?? '') !== 'admin')) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }
        } else {
            // use authenticated user's professional record
            $professional = Professional::where('user_id', $user->id)->firstOrFail();
        }

        $data['professional_id'] = $professional->id;

        $service = Service::create($data);
        $service->load('professional.user');


        return response()->json($service, 201);
    }

    public function show(string $id)
    {
        return Service::findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $service = Service::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
        ]);

        $service->update($data);
        $service->load('professional.user');

        return response()->json($service);
    }

    public function destroy(string $id)
    {
        $service = Service::findOrFail($id);

        $service->delete();

        return response()->json([
            'message' => 'Servicio eliminado correctamente.'
        ]);
    }
}
