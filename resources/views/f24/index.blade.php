@extends('layouts.app')

@section('title', 'F24 - Lista')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">F24</h1>
        <div class="flex space-x-4">
            <a href="{{ route('tasse.calculator') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                Importa F24
            </a>
        </div>
    </div>

    <!-- Statistiche -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Totale F24</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">In Attesa</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pagati</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['paid'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Scaduti</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['overdue'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtri -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Stato Pagamento</label>
                <select name="payment_status" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Tutti</option>
                    <option value="PENDING" {{ request('payment_status') == 'PENDING' ? 'selected' : '' }}>In Attesa</option>
                    <option value="PAID" {{ request('payment_status') == 'PAID' ? 'selected' : '' }}>Pagati</option>
                    <option value="OVERDUE" {{ request('payment_status') == 'OVERDUE' ? 'selected' : '' }}>Scaduti</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sezione</label>
                <select name="section" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Tutte</option>
                    <option value="erario" {{ request('section') == 'erario' ? 'selected' : '' }}>Erario</option>
                    <option value="inps" {{ request('section') == 'inps' ? 'selected' : '' }}>INPS</option>
                    <option value="imu" {{ request('section') == 'imu' ? 'selected' : '' }}>IMU</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Anno</label>
                <select name="year" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Tutti</option>
                    @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endfor
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Filtra
                </button>
                <a href="{{ route('f24.index') }}" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Lista F24 -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($f24s->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                File
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Importo
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sezioni
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Anni
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Scadenza
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stato
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Azioni
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($f24s as $f24)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $f24->filename }}</div>
                                    <div class="text-sm text-gray-500">{{ $f24->imported_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $f24->getFormattedAmount() }}</div>
                                    <div class="text-sm text-gray-500">{{ $f24->getTaxesCount() }} voci</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $f24->getSectionsList() }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $f24->getReferenceYearsList() }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($f24->due_date)
                                        <div class="text-sm text-gray-900">{{ $f24->due_date->format('d/m/Y') }}</div>
                                        @if($f24->isOverdue())
                                            <div class="text-sm text-red-600">Scaduto</div>
                                        @endif
                                    @else
                                        <div class="text-sm text-gray-500">-</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
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
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('f24.show', $f24) }}" class="text-blue-600 hover:text-blue-900">
                                            Visualizza
                                        </a>
                                        <a href="{{ route('f24.download', $f24) }}" class="text-green-600 hover:text-green-900">
                                            Scarica
                                        </a>
                                        @if($f24->payment_status === 'PENDING')
                                            <form method="POST" action="{{ route('f24.mark-as-paid', $f24) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                    Paga
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('f24.destroy', $f24) }}" class="inline" onsubmit="return confirm('Sei sicuro di voler eliminare questo F24?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                Elimina
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginazione -->
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $f24s->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Nessun F24 trovato</h3>
                <p class="mt-1 text-sm text-gray-500">Inizia importando il tuo primo F24.</p>
                <div class="mt-6">
                    <a href="{{ route('tasse.calculator') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Importa F24
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
