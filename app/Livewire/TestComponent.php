<?php

namespace App\Livewire;

use Livewire\Component;

class TestComponent extends Component
{
    public $message = 'Test component is working!';

    public function testMethod()
    {
        $this->message = 'Test method called successfully!';
    }

    public function render()
    {
        return view('livewire.test-component');
    }
} 