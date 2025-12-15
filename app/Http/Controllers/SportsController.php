<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use Illuminate\Http\Request;

class SportsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sports = Sport::orderBy('name')->paginate(15);
        return response()->json($sports);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'slug' => 'required',
        ]);
        $sports = Sport::create($request->all());
        return response()->json($sports);
        if (!$sports) {
            return response()->json(['message' => 'Sport not created'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Sport $sport)
    {
        $sports = Sport::find($sport->id);
        return response()->json($sports);
        if (!$sports) {
            return response()->json(['message' => 'Sport not found'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sport $sport)
    {
        $sports = Sport::find($sport->id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sport $sport)
    {
        $validated = $request->validate([
            'name' => 'required',
            'slug' => 'required',
        ]);
        $sports = Sport::find($sport->id);
        $sports->update($request->all());
        return response()->json($sports);
        if (!$sports) {
            return response()->json(['message' => 'Sport not updated'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sport $sport)
    {
        $sports = Sport::find($sport->id);
        $sports->delete();
        return response()->json(['message' => 'Sport deleted'], 200);
        if (!$sports) {
            return response()->json(['message' => 'Sport not deleted'], 404);
        }
    }
}
