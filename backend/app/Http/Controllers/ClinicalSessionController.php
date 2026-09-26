<?php

namespace App\Http\Controllers;

use App\Models\ClinicalSession;
use Illuminate\Http\Request;

class ClinicalSessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ClinicalSession::with([
            'clinicalHistory.user',
            'appointment.professional.user',
        ])->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'clinical_history_id' => 'required|integer|exists:clinical_histories,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'session_number' => 'nullable|integer|max:255',
            'topic' => 'required|string|max:255',
            'evolution' => 'required|string',
            'observations' => 'nullable|string',
        ]);

        $clinicalSession = ClinicalSession::create($data);
        return response()->json($clinicalSession, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return ClinicalSession::with([
            'clinicalHistory.user',
            'appointment.professional.user',
        ])->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $clinicalSession = ClinicalSession::findOrFail($id);
        $data = $request->validate([
            'clinical_history_id' => 'required|integer|exists:clinical_histories,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'session_number' => 'nullable|string|max:255',
            'topic' => 'required|string|max:255',
            'evolution' => 'required|string',
            'observations' => 'nullable|string',
        ]);

        $clinicalSession->update($data);
        return response()->json($clinicalSession, 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $clinicalSession = ClinicalSession::findOrFail($id);
        $clinicalSession->delete();
        return response()->noContent();
    }
}
