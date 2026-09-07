<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Appointment::with(['professional.user', 'service'])
            ->where('user_id', Auth::id())
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }

    /**
     * Display a listing of the resource for the authenticated professional.
     */
    public function professionalAppointments(Request $request)
    {
        $professional = Auth::user()->professional;
        $appointments = Appointment::with(['user', 'service'])
            ->where('professional_id', $professional->id)
            ->orderBy('date')
            ->orderBy('time')
            ->get();
        return response()->json($appointments);
    }


    /**
     * Display a listing of the resource for the authenticated admin.
     */
    public function adminAppointments()
    {
        $appointments = Appointment::with([
            'professional.user',
            'user',
            'service'
        ])
            ->orderBy('date')
            ->orderBy('time')
            ->get();
        return response()->json($appointments);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'professional_id' => 'required|exists:professionals,id',
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);

        $appointment = Appointment::create($data);

        return response()->json($appointment, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Appointment::with(['professional', 'service'])->findOrFail($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, string $id)
    {
        $appointment = Appointment::findOrFail($id);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'professional_id' => 'required|exists:professionals,id',
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);

        $appointment->update($data);

        return response()->json($appointment);
    }

    public function cancel(Appointment $appointment)
    {
        if ($appointment->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'No estás autorizado para cancelar este turno.'
            ], 403);
        }

        $appointment->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'message' => 'Turno cancelado correctamente.',
            'appointment' => $appointment,
        ]);
    }

    public function complete(Appointment $appointment)
    {
        if ($appointment->professional_id !== Auth::user()->professional->id) {
            return response()->json([
                'message' => 'No estás autorizado para completar este turno.'
            ], 403);
        }

        $appointment->update([
            'status' => 'completed',
        ]);

        return response()->json([
            'message' => 'Turno completado correctamente.',
            'appointment' => $appointment,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $appointment = Appointment::findOrFail($id);

        $appointment->delete();

        return response()->json([
            'message' => 'Turno eliminado correctamente.'
        ]);
    }
}
