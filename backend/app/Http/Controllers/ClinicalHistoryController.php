<?php

namespace App\Http\Controllers;

use App\Models\ClinicalHistory;
use App\Models\Professional;
use Illuminate\Http\Request;

class ClinicalHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $professional = Professional::where(
            'user_id',
            $request->user()->id
        )->first();

        // Cliente: devuelve únicamente su propia historia clínica.
        if (!$professional) {
            return ClinicalHistory::with('user')
                ->where('user_id', $request->user()->id)
                ->get();
        }

        // Profesional: historias de los pacientes con turnos asignados.
        return ClinicalHistory::with('user')
            ->whereHas('user.appointments', function ($query) use ($professional) {
                $query->where('professional_id', $professional->id);
            })
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'chief_complaint' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'initial_assessment' => 'nullable|string',
            'clinical_impression' => 'nullable|string',
            'therapeutic_goals' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $clinicalHistory = ClinicalHistory::create($data);
        return response()->json($clinicalHistory, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return ClinicalHistory::with('user')->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $clinicalHistory = ClinicalHistory::findOrFail($id);
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'chief_complaint' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'initial_assessment' => 'nullable|string',
            'clinical_impression' => 'nullable|string',
            'therapeutic_goals' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $clinicalHistory->update($data);
        return response()->json($clinicalHistory, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $clinicalHistory = ClinicalHistory::findOrFail($id);
        $clinicalHistory->delete();
        return response()->json('Historia clinica eliminada correctamente', 204);
    }
}
