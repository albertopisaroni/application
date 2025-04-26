<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Registration;

class EditRegistrationField extends Component
{
    use WithFileUploads;

    public Registration $registration;
    public string $field;
    public $value;
    public bool $editing = false;

    public function mount(Registration $registration, string $field)
    {
        $this->registration = $registration;
        $this->field = $field;
        $this->value = $registration->{$field};
    }

    public function save()
    {
        if (in_array($this->field, ['document_front', 'document_back'])) {
            if ($this->value && $this->value->isValid()) {
                $folder = 'newo/registrazioni/' . $this->registration->uuid;
                $ext = $this->value->getClientOriginalExtension();
                
                $path = $this->value->storeAs($folder, $this->field === 'document_front' ? "fronte.$ext" : "retro.$ext", 's3');

                $this->registration->update([
                    $this->field => $path,
                ]);

                $this->dispatch('registrationUpdated');

                $this->value = null; // svuota il file input
                $this->editing = false;
            }
            return;
        }

        // Altri campi
        $this->registration->update([
            $this->field => $this->value,
        ]);
        $this->dispatch('registrationUpdated');
        $this->editing = false;
    }

    public function render()
    {
        return view('livewire.admin.edit-registration-field');
    }
}