<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AbbonamentiController extends Controller
{
    public function lista()
    {
        return view('abbonamenti.lista');
    }
}
