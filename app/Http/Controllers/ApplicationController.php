<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function dashboard()
    {
        return view('app.dashboard');
    }
}
