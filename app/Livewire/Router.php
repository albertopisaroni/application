<?php

namespace App\Livewire;

use Livewire\Component;

class Router extends Component
{
    public function render()
    {
        // Prendi la route corrente (es. "fatture", "dashboard", etc.)
        $segment = request()->segment(1) ?? 'dashboard';

        return view("livewire.pages.$segment");
    }
}