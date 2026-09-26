<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string',
            'phone' => 'sometimes|required|string|max:15',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'lastName' => $validated['lastName'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
        ]);

        // Si el usuario es profesional, crear registro en professionals
        if (($validated['role'] ?? null) === 'professional') {
            Professional::create([
                'user_id' => $user->id,
                'specialty' => 'Sin especialidad',
                'description' => '',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);

    }

    public function login(Request $request)
    {
        $user = User::where(
            'email',
            $request->email
        )->first();


        if (!$user || !Hash::check($request->password, $user->password)) {

            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);

        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ]);
    }
}
