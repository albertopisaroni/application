<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SpeseController extends Controller
{
    /**
     * Mostra l'elenco di tutte le spese.
     */
    public function list()
    {
        return view('spese.lista');
    }
}
