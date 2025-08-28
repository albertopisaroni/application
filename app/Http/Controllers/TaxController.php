<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TaxController extends Controller
{
        /**
     * Mostra l'elenco di tutte le tasse.
     */
    public function list()
    {
        return view('tasse.lista');
    }
}
