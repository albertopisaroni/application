<?php

namespace App\Livewire\App;

use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.app.dashboard')->layout('layouts.app');
    }
}
