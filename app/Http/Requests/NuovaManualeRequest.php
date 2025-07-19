<?php
// app/Http/Requests/NuovaManualeRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NuovaManualeRequest extends FormRequest
{
    public function authorize() { return true; }

    /**
     * Trasforma in array singoli oggetti "articoli" o "scadenze"
     * che Scribe spesso genera come oggetto invece che array.
     */
    protected function prepareForValidation()
    {
        // Se "cliente" non è un array o è un oggetto singolo, lo trasformo in array vuoto o corretto
        if (!is_array($this->input('cliente')) || array_keys($this->input('cliente')) === range(0, count($this->input('cliente')) - 1)) {
            $this->merge([
                'cliente' => [],
            ]);
        }

        // Se "articoli" non è un array o è un singolo oggetto → lo avvolgo in array
        if (!is_array($this->input('articoli')) || array_keys($this->input('articoli')) !== range(0, count($this->input('articoli')) - 1)) {
            $this->merge([
                'articoli' => [$this->input('articoli')],
            ]);
        }

        // Stessa cosa per "scadenze"
        if ($this->has('scadenze')) {
            $scadenze = $this->input('scadenze');
            if (!is_array($scadenze) || array_keys($scadenze) !== range(0, count($scadenze) - 1)) {
                $this->merge([
                    'scadenze' => [$scadenze],
                ]);
            }
        }
    }

    public function rules()
    {
        return [
            // --- cliente via API ---
            'cliente.name'     => 'required|string',
            'cliente.piva'     => 'required|string',
            'cliente.address'  => 'required|string',
            'cliente.cap'      => 'required|string',
            'cliente.city'     => 'required|string',
            'cliente.country'  => 'required|string',
            'cliente.sdi'      => 'nullable|string',
            'cliente.pec'      => 'nullable|email',
            'cliente.email'    => 'nullable|email',
            'cliente.phone'    => 'nullable|string',

            // --- numerazione e documento ---
            'numerazione'       => 'required|string|exists:invoice_numberings,name',
            'issue_date'        => 'required|date',
            'tipo_documento'    => 'nullable|in:TD01,TD01_ACC,TD24,TD25',
            'sconto'            => 'nullable|numeric|min:0',
            'intestazione'      => 'nullable|string',
            'note'              => 'nullable|string',
            'metodo_pagamento'  => 'required|string|exists:payment_methods,name',
            'paid'              => 'nullable|numeric|min:0',

            // --- articoli ---
            'articoli'             => 'required|array|min:1',
            'articoli.*.nome'      => 'required|string',
            'articoli.*.quantita'  => 'required|numeric|min:0.01',
            'articoli.*.prezzo'    => 'required|numeric|min:0',
            'articoli.*.iva'       => 'required|numeric|min:0',
            'articoli.*.descrizione'=> 'nullable|string',

            // --- scadenze (opzionale) ---
            'scadenze'             => 'nullable|array',
            'scadenze.*.date'      => 'required_with:scadenze|date',
            'scadenze.*.value'     => 'required_with:scadenze|numeric|min:0',
            'scadenze.*.type'      => 'required_with:scadenze|in:percent,amount',

            'invia_sdi'             => 'nullable|boolean',
            'emails'                => 'nullable|array',
            'emails.*'              => 'email',

        ];
    }
}