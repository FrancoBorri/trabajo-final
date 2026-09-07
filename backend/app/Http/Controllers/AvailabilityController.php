<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Appointment;
use Carbon\Carbon;


class AvailabilityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Availability::all();
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
            'day_week' => 'required|integer|between:1,7',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start',
        ]);

        $professional = $request->user()->professional;
        if (!$professional) {
        return response()->json([
            'message' => 'El usuario no tiene un profesional asociado.'
        ], 422);
        }

        $availability = $professional->availability()->create($data);

        return response()->json($availability, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Availability::findOrFail($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $availability = Availability::findOrFail($id);

        $data = $request->validate([
            'day_week' => 'required|integer|between:1,7',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start',
        ]);

        $availability->update($data);

        return response()->json($availability);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $availability = Availability::findOrFail($id);

        $availability->delete();

        return response()->json([
            'message' => 'Disponibilidad eliminada correctamente.'
        ]);
    }


    /**
    * Get available appointment slots.
    */
    public function availableSlots(Request $request, int $professionalId)
    {
        $data = $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'date' => 'required|date',
        ]);

        // Buscar el servicio
        $service = Service::findOrFail($data['service_id']);

        // Verificar que el servicio pertenezca al profesional
        if ($service->professional_id !== $professionalId) {
            return response()->json([
                'message' => 'El servicio no pertenece a este profesional.'
            ], 422);
        }

        // Convertir fecha
        $date = Carbon::parse($data['date']);

        // 1 = lunes ... 7 = domingo
        $dayOfWeek = $date->dayOfWeekIso;

        // Buscar disponibilidad del profesional para ese día
        $availability = Availability::where('professional_id', $professionalId)
            ->where('day_week', $dayOfWeek)
            ->first();

        // El profesional no trabaja ese día
        if (!$availability) {
            return response()->json([
                'date' => $date->toDateString(),
                'slots' => [],
            ]);
        }

        // Duración del servicio en minutos
        $duration = $service->duration;

        // Horario laboral
        $workStart = Carbon::parse(
            $date->toDateString() . ' ' . $availability->time_start
        );

        $workEnd = Carbon::parse(
            $date->toDateString() . ' ' . $availability->time_end
        );

        // Turnos existentes ese día
        $appointments = Appointment::where('professional_id', $professionalId)
            ->whereDate('date', $date->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $slots = [];

        $current = $workStart->copy();

        while (
            $current->copy()
                ->addMinutes($duration)
                ->lessThanOrEqualTo($workEnd)
        ) {
            $candidateStart = $current->copy();

            $candidateEnd = $current->copy()
                ->addMinutes($duration);

            // Verificar si se superpone con otro turno
            $hasConflict = $appointments->contains(function ($appointment) use (
                $candidateStart,
                $candidateEnd,
                $date
            ) {
                $appointmentStart = Carbon::parse(
                    $date->toDateString() . ' ' . $appointment->time
                );

                $appointmentEnd = $appointmentStart->copy()
                    ->addMinutes($appointment->service->duration);

                return $candidateStart->lt($appointmentEnd)
                    && $candidateEnd->gt($appointmentStart);
            });

            if (!$hasConflict) {
                $slots[] = $candidateStart->format('H:i');
            }

            $current->addMinutes($duration);
        }

        return response()->json([
            'date' => $date->toDateString(),
            'service_id' => $service->id,
            'duration' => $duration,
            'slots' => $slots,
        ]);
    }
}
