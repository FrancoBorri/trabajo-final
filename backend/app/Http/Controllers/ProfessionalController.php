<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class ProfessionalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 7);

        $professionals = Professional::with('user')
            ->paginate($perPage);

        $professionals->getCollection()->transform(function ($professional) {
            return [
                'id' => $professional->id,

                // Datos del usuario
                'name' => $professional->user->name,
                'lastName' => $professional->user->lastName,
                'email' => $professional->user->email,
                'phone' => $professional->user->phone,
                'avatar' => $professional->user->avatar,

                // Datos del profesional
                'specialty' => $professional->specialty,
                'description' => $professional->description,
                'created_at' => $professional->created_at,
                'updated_at' => $professional->updated_at,
            ];
        });

        return response()->json($professionals);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([

            // Datos usuario
            'name' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',

            // Datos profesional
            'specialty' => 'required|string|max:255',
            'description' => 'nullable|string',

        ]);


        // Crear usuario
        $user = User::create([
            'name' => $data['name'],
            'lastName' => $data['lastName'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'professional', // se crea por defecto con el rol profesional
        ]);


        // Crear profesional relacionado
        $professional = Professional::create([
            'user_id' => $user->id,
            'specialty' => $data['specialty'],
            'description' => $data['description'] ?? null,
        ]);

        return response()->json(
            $professional->load('user'),
            201
        );
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Professional::with('user')
            ->findOrFail($id);
    }


    /**
     * Update the specified resource.
     */
    public function update(Request $request, string $id)
    {
        $professional = Professional::findOrFail($id);


        $data = $request->validate([
            'specialty' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);


        $professional->update($data);


        return response()->json(
            $professional->load('user')
        );
    }


    public function profile(Request $request)
    {
        $professional = Professional::with('user')
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$professional) {
            return response()->json([
                'message' => 'Solo los profesionales pueden acceder a su perfil.'
            ], 403);
        }

        return response()->json([
            'id' => $professional->id,

            // Usuario
            'name' => $professional->user->name,
            'lastName' => $professional->user->lastName,
            'email' => $professional->user->email,
            'phone' => $professional->user->phone,
            'avatar' => $professional->user->avatar,

            // Profesional
            'specialty' => $professional->specialty,
            'description' => $professional->description,

            'created_at' => $professional->created_at,
            'updated_at' => $professional->updated_at,
        ]);
    }


    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $professional = Professional::where('user_id', $user->id)
            ->first();

        if (!$professional) {
            return response()->json([
                'message' => 'Solo los profesionales pueden editar su perfil.'
            ], 403);
        }

        $data = $request->validate([
            // Datos usuario
            'name' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',

            // Datos profesional
            'specialty' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Actualizar usuario
        $user->update([
            'name' => $data['name'],
            'lastName' => $data['lastName'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        // Actualizar profesional
        $professional->update([
            'specialty' => $data['specialty'],
            'description' => $data['description'] ?? null,
        ]);

        return response()->json([
            'id' => $professional->id,

            'name' => $user->name,
            'lastName' => $user->lastName,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,

            'specialty' => $professional->specialty,
            'description' => $professional->description,

            'created_at' => $professional->created_at,
            'updated_at' => $professional->updated_at,
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $professional = Professional::findOrFail($id);

        // Eliminamos el profesional
        $professional->delete();

        // Eliminamos también el usuario asociado
        $professional->user()->delete();

        return response()->json([
            'message' => 'Profesional eliminado correctamente.'
        ]);
    }
}
