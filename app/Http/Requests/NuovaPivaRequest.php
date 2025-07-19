<?php
// app/Http/Requests/NuovaPivaRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class NuovaPivaRequest extends FormRequest
{
    public function authorize() { return true; }

    protected function prepareForValidation()
    {
        // se 'articoli' è un singolo oggetto (ha chiave 'nome'), wrap in array
        if ($this->has('articoli') && is_array($this->input('articoli')) && array_key_exists('nome', $this->input('articoli'))) {
            $this->merge([
                'articoli' => [ $this->input('articoli') ],
            ]);
        }

        // stessa logica per 'scadenze' (singolo oggetto ha chiave 'date')
        if ($this->has('scadenze') && is_array($this->input('scadenze')) && array_key_exists('date', $this->input('scadenze'))) {
            $this->merge([
                'scadenze' => [ $this->input('scadenze') ],
            ]);
        }
    }

    public function rules()
    {
        return [
            'piva'             => 'required|string|size:11',
            'numerazione'      => 'nullable|string|exists:invoice_numberings,name',
            'issue_date'       => 'nullable|date',
            'tipo_documento'   => 'nullable|in:TD01,TD01_ACC,TD24,TD25',
            'metodo_pagamento' => 'nullable|string|exists:payment_methods,name',
            'sconto'           => 'nullable|numeric|min:0',
            'intestazione'     => 'nullable|string',
            'note'             => 'nullable|string',
            'invia_sdi'        => 'nullable|boolean',

            'emails'     => 'nullable|array',
            'emails.*'   => 'email',

            'articoli'               => 'required|array|min:1',
            'articoli.*.nome'        => 'required|string',
            'articoli.*.quantita'    => 'required|numeric|min:0.01',
            'articoli.*.prezzo'      => 'required|numeric|min:0',
            'articoli.*.iva'         => 'nullable|numeric|min:0',
            'articoli.*.descrizione' => 'nullable|string',

            'scadenze'               => 'nullable|array',
            'scadenze.*.date'        => 'required_with:scadenze|date',
            'scadenze.*.value'       => 'required_with:scadenze|numeric|min:0',
            'scadenze.*.type'        => 'required_with:scadenze|in:percent,amount',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $scadenze = $this->input('scadenze');
            $issueDate = $this->input('issue_date') ?? now()->toDateString();
        });
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation errors',
            'errors'  => $validator->errors(),
        ], 422));
    }
}