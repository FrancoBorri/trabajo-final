<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Service::with('professional.user');

        // Professionals manage only their own services; other authenticated
        // roles need the full catalog to book or administer them.
        if ($user?->role === 'professional') {
            $professional = Professional::where('user_id', $user->id)->first();

            if (!$professional) {
                return response()->json([]);
            }

            $query->where('professional_id', $professional->id);
        }

        return $query->orderBy('title')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $professional = Professional::where('user_id', $user->id)->firstOrFail();

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
