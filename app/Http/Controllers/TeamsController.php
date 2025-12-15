<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TeamsController extends Controller
{
    //
    public function index(Request $request){
        $teams = Team::with('sport') 
        ->when($request->search, fn($q) =>
        $q->where('name', 'like', '%' . $request->search . '%'))
        ->paginate(10);
        return response()->json($teams);
    }
}
