<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Registration;

class AdminController extends Controller
{
    public function registrationIndex(Request $request)
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $direction = $request->get('direction', 'desc');

        $filters = $request->only(['project_type', 'contacted', 'registered']);
        $query = Registration::query();
        
        if (filled($filters['project_type'] ?? null)) {
            $query->where('project_type', $filters['project_type']);
        }
        
        if (filled($filters['contacted'] ?? null)) {
            $query->where('contacted', $filters['contacted']);
        }
        
        if (filled($filters['registered'] ?? null)) {
            $query->where('registered', $filters['registered']);
        }

        $registrations = $query->orderBy($sortBy, $direction)->paginate(20);

        return view('app.admin.registrations.index', compact('registrations', 'sortBy', 'direction'));
    }

    public function show(Registration $registration)
    {
        return view('app.admin.registrations.show', compact('registration'));
    }

}
