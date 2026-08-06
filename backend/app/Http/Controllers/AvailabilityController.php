<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use Illuminate\Http\Request;

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
            'professional_id' => 'required|exists:professionals,id',
            'day_week' => 'required|integer|between:1,7',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start',
        ]);

        $availability = Availability::create($data);

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
            'professional_id' => 'required|exists:professionals,id',
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
}
