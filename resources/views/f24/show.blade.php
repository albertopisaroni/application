@extends('layouts.app')

@section('title', 'F24 - Dettagli')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">F24 - {{ $f24->filename }}</h1>
            <p class="text-gray-600 mt-2">Importato il {{ $f24->imported_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex space-x-4">
            <a href="{{ route('f24.download', $f24) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                Scarica PDF
            </a>
            <a href="{{ route('f24.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                Torna alla lista
            </a>
        </div>
    </div>

    <!-- Informazioni generali -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informazioni Generali</h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Importo Totale</dt>
                    <dd class="text-2xl font-bold text-gray-900">{{ $f24->getFormattedAmount() }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Stato Pagamento</dt>
                    <dd>
                        @switch($f24->payment_status)
                            @case('PENDING')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    In Attesa
                                </span>
                                @break
                            @case('PAID')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Pagato
                                </span>
                                @break
                            @case('OVERDUE')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Scaduto
                                </span>
                                @break
                            @default
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $f24->payment_status }}
                                </span>
                        @endswitch
                    </dd>
                </div>
                @if($f24->due_date)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Data Scadenza</dt>
                        <dd class="text-sm text-gray-900">
                            {{ $f24->due_date->format('d/m/Y') }}
                            @if($f24->isOverdue())
                                <span class="text-red-600 ml-2">(Scaduto)</span>
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Sezioni</h3>
            <div class="space-y-2">
                @foreach($f24->sections ?? [] as $section)
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ ucfirst($section) }}
                        </span>
                        <span class="ml-2 text-sm text-gray-600">
                            ({{ $taxesBySection->get($section, collect())->count() }} voci)
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Anni di Riferimento</h3>
            <div class="space-y-2">
                @foreach($f24->reference_years ?? [] as $year)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ $year }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Azioni -->
    @if($f24->payment_status === 'PENDING')
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Azioni</h3>
            <form method="POST" action="{{ route('f24.mark-as-paid', $f24) }}" class="flex items-center space-x-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Pagamento</label>
                    <input type="date" name="paid_date" value="{{ date('Y-m-d') }}" class="border-gray-300 rounded-md shadow-sm">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                        Marca come Pagato
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Dettagli Tasse -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Dettagli Tasse ({{ $f24->getTaxesCount() }} voci)</h3>
        </div>

        @if($f24->taxes->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sezione
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Codice
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Descrizione
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Anno
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Importo
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stato
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($f24->taxes as $tax)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @if($tax->section_type === 'erario') bg-blue-100 text-blue-800
                                        @elseif($tax->section_type === 'inps') bg-green-100 text-green-800
                                        @elseif($tax->section_type === 'imu') bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($tax->section_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $tax->tax_code }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $tax->description }}</div>
                                    @if($tax->notes)
                                        <div class="text-sm text-gray-500">{{ $tax->notes }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $tax->tax_year }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $tax->getFormattedAmount() }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @switch($tax->payment_status)
                                        @case('PENDING')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                In Attesa
                                            </span>
                                            @break
                                        @case('PAID')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Pagato
                                            </span>
                                            @break
                                        @case('OVERDUE')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Scaduto
                                            </span>
                                            @break
                                        @default
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ $tax->payment_status }}
                                            </span>
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Nessuna tassa trovata</h3>
                <p class="mt-1 text-sm text-gray-500">Questo F24 non contiene voci di tassa.</p>
            </div>
        @endif
    </div>
</div>
@endsection
