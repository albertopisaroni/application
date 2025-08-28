<div class="px-2">
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center space-x-4">
            <h1 class="text-3xl font-normal">Tasse e Tributi</h1>
            <div class="flex bg-gray-200 rounded-lg p-1">
                <button wire:click="switchViewMode('taxes')" 
                        class="px-3 py-1 text-sm font-medium rounded-md transition-colors {{ $viewMode === 'taxes' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                    Tasse
                </button>
                <button wire:click="switchViewMode('f24')" 
                        class="px-3 py-1 text-sm font-medium rounded-md transition-colors {{ $viewMode === 'f24' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                    F24
                </button>
            </div>
        </div>

        <div class="flex items-center gap-x-4">
            @if ($yearFilter || $search || $paymentStatusFilter)
                <button wire:click="resetFilters" class="items-center gap-x-2 flex bg-[#e8e8e8] pr-4 pl-3 py-2 text-sm rounded-[4px] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>                      
                    Elimina filtri
                </button>
            @endif

            <select wire:model.live="yearFilter" class="text-[#050505] invalid:text-[#aba7af] border border-[#e8e8e8] rounded px-3 py-2 text-sm pr-8" required>
                <option value="null" disabled selected hidden>Seleziona anno</option>
                @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endfor
            </select>

            <select wire:model.live="paymentStatusFilter" class="text-[#050505] invalid:text-[#aba7af] border border-[#e8e8e8] rounded px-3 py-2 text-sm pr-8" required>
                <option value="null" disabled selected hidden>Stato pagamento</option>
                <option value="unpaid">In Attesa</option>
                <option value="paid">Pagati</option>
            </select>

            <button id="f24ImportBtn" class="items-center gap-x-2 flex bg-black text-white px-4 py-2 text-sm rounded-[4px] transition hover:bg-gray-800">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6z" stroke="currentColor" stroke-width="1.5" fill="none"/>
                    <path d="M6 8h4M6 10h2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M5 4V2a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                Importa F24
            </button>
        </div>
    </div>

    <div class="relative flex mb-6 bg-[#f5f5f5] rounded-[4px] items-center pl-4 pr-2">
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
            <mask id="mask0_569_5714" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="16" height="16">
            <path d="M0 0H16V16H0V0Z" fill="white"/>
            </mask>
            <g mask="url(#mask0_569_5714)">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M11.8875 11.2198L8.775 8.09977C9.4425 7.25977 9.84 6.20227 9.84 5.05477C9.84 2.33227 7.635 0.134766 4.92 0.134766C2.205 0.134766 0 2.33227 0 5.05477C0 7.77727 2.205 9.97477 4.92 9.97477C6.21 9.97477 7.38 9.47977 8.2575 8.66227L11.355 11.7523C11.505 11.9023 11.745 11.9023 11.8875 11.7523C12.0375 11.6098 12.0375 11.3698 11.8875 11.2198ZM4.92 9.21727C2.6175 9.21727 0.7575 7.34977 0.7575 5.05477C0.7575 2.75977 2.6175 0.892266 4.92 0.892266C7.2225 0.892266 9.0825 2.75227 9.0825 5.05477C9.0825 7.35727 7.2225 9.21727 4.92 9.21727Z" fill="#050505"/>
            </g>
        </svg>
        <input id="searchInput" wire:model.debounce.live.500ms="search" type="text" placeholder="Cerca per nome file F24" class="bg-[#f5f5f5] rounded-[4px] border-0 ring-0 focus:ring-0 pr-4 py-2 w-full text-sm">
        <div id="shortcutHint" class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400 bg-white border border-gray-300 rounded px-1 py-0.5 pointer-events-none">
            ⌘K
        </div>
    </div>

    @if($filteredF24)
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 2a6 6 0 1 0 0 12A6 6 0 0 0 8 2ZM1 8a7 7 0 1 1 14 0A7 7 0 0 1 1 8Z" stroke="currentColor" stroke-width="1"/>
                        <path d="M8 5v3l2 2" stroke="currentColor" stroke-width="1" stroke-linecap="round"/>
                    </svg>
                    <span class="text-sm font-medium text-green-800">
                        Visualizzando F24 selezionato: <strong>{{ $filteredF24->filename }}</strong>
                    </span>
                </div>
                <button wire:click="clearFilters" class="text-green-600 hover:text-green-800 text-sm">
                    Rimuovi filtro
                </button>
            </div>
        </div>
    @endif

    <div class="mb-3 text-sm text-gray-600 flex items-center gap-x-8">
        <button wire:click="$set('paymentStatusFilter', 'unpaid')" class="text-[#FC460E] hover:font-semibold focus:outline-none {{ $paymentStatusFilter === 'unpaid' ? 'font-semibold' : '' }}">
            Da pagare: {{ $unpaidCount }}
        </button>
    
        <button wire:click="$set('paymentStatusFilter', 'paid')" class="hover:font-semibold focus:outline-none {{ $paymentStatusFilter === 'paid' ? 'font-semibold' : '' }}">
            Pagati: {{ $paidCount }}
        </button>
            
        <div class="bg-[#dcf3fd] text-[#616161] rounded-[6.75px] px-4 py-2 text-sm flex gap-x-1 items-center">
            <svg width="6" height="15" viewBox="0 0 6 15" fill="none" xmlns="http://www.w3.org/2000/svg" class="mr-1">
                <g filter="url(#filter0_d_913_5566)">
                <path d="M3.924 9.39754H1.92491L1.50494 1.3844H4.34397L3.924 9.39754ZM1.47134 12.203C1.47134 11.6878 1.61133 11.3294 1.89131 11.1278C2.1713 10.915 2.51288 10.8087 2.91605 10.8087C3.30803 10.8087 3.64401 10.915 3.924 11.1278C4.20398 11.3294 4.34397 11.6878 4.34397 12.203C4.34397 12.6957 4.20398 13.0541 3.924 13.2781C3.64401 13.4909 3.30803 13.5973 2.91605 13.5973C2.51288 13.5973 2.1713 13.4909 1.89131 13.2781C1.61133 13.0541 1.47134 12.6957 1.47134 12.203Z" fill="#616161"/>
                </g>
                <defs>
                <filter id="filter0_d_913_5566" x="0.771385" y="0.684806" width="4.27254" height="13.6128" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                <feOffset/>
                <feGaussianBlur stdDeviation="0.34998"/>
                <feComposite in2="hardAlpha" operator="out"/>
                <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.01 0"/>
                <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_913_5566"/>
                <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_913_5566" result="shape"/>
                </filter>
                </defs>
            </svg>
            Ci sono <strong>{{ $unpaidCount }} F24</strong> da pagare per un totale di <strong>€ {{ number_format($unpaidTotal, 2, ',', '.') }}</strong>
        </div>
    </div>

    <div class="">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="text-[#616161] text-xs border-b">
                    <th class="py-2 pl-2 pr-4 font-normal">File</th>
                    <th class="py-2 px-4 font-normal">Sezioni</th>
                    <th class="py-2 px-4 font-normal">Anni</th>
                    <th class="py-2 px-4 font-normal">Stato pagamento</th>
                    <th class="py-2 px-4 font-normal">Importo</th>
                    <th class="py-2 px-4 font-normal">Scadenza</th>
                    <th class="py-2 px-4 font-normal">Data importazione</th>
                    <th class="py-2 px-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($f24s as $f24)
                <tr class="hover:bg-[#f5f5f5] bg-white group transition-all duration-200">
                    <!-- File -->
                    <td class="whitespace-nowrap py-4 pl-2 pr-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-normal text-gray-900 truncate">
                                    {{ $f24->filename }}
                                </div>
                                <div class="text-xs text-[#616161] truncate">
                                    {{ $f24->getTaxesCount() }} voci
                                </div>
                            </div>
                        </div>
                    </td>
                    
                    <!-- Sezioni -->
                    <td class="whitespace-nowrap py-4 px-4 font-normal text-gray-800">
                        <div class="flex flex-wrap gap-1">
                            @foreach($f24->sections ?? [] as $section)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                    @if($section === 'erario') bg-blue-100 text-blue-800
                                    @elseif($section === 'inps') bg-green-100 text-green-800
                                    @elseif($section === 'imu') bg-purple-100 text-purple-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($section) }}
                                </span>
                            @endforeach
                        </div>
                    </td>
                    
                    <!-- Anni -->
                    <td class="whitespace-nowrap py-4 px-4 font-mono text-sm">
                        <div class="flex flex-wrap gap-1">
                            @foreach($f24->reference_years ?? [] as $year)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ $year }}
                                </span>
                            @endforeach
                        </div>
                    </td>
                    
                    <!-- Stato pagamento -->
                    <td class="py-4 px-4 whitespace-nowrap">  
                        <div class="flex flex-col gap-1">
                            @if ($f24->payment_status === 'PAID')
                                <span class="bg-[#edffef] text-[#1b7101] text-xs font-medium px-3 py-1 rounded-[4px]">
                                    Pagato
                                </span>
                            @elseif ($f24->payment_status === 'PARTIALLY_PAID')
                                <span class="bg-[#fef7e0] text-[#d97706] text-xs font-medium px-3 py-1 rounded-[4px]">
                                    Parzialmente pagato
                                </span>
                            @elseif ($f24->payment_status === 'OVERDUE')
                                <span class="bg-[#fef2f2] text-[#dc2626] text-xs font-medium px-3 py-1 rounded-[4px]">
                                    Scaduto
                                </span>
                            @elseif ($f24->payment_status === 'CANCELLED')
                                <span class="bg-gray-100 text-gray-700 text-xs font-medium px-3 py-1 rounded-[4px]">
                                    Annullato
                                </span>
                            @else
                                <span class="bg-[#fff4f0] text-[#FC460E] text-xs font-medium px-3 py-1 rounded-[4px]">
                                    Da pagare
                                </span>
                            @endif
                            
                            @if($f24->hasReceipt())
                                <span class="bg-blue-100 text-blue-700 text-xs font-medium px-2 py-1 rounded-[4px] flex items-center gap-1">
                                    <svg width="12" height="12" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z" fill="currentColor"/>
                                    </svg>
                                    Ricevuta
                                </span>
                            @endif
                        </div>
                    </td>
                    
                    <!-- Importo -->
                    <td class="whitespace-nowrap py-4 px-4 text-gray-900 font-medium">
                        {{ $f24->getFormattedAmount() }}
                    </td>
                    
                    <!-- Scadenza -->
                    <td class="whitespace-nowrap py-4 px-4 text-gray-700">
                        @if($f24->due_date)
                            <span class="{{ $f24->isOverdue() ? 'text-red-600 font-medium' : '' }}">
                                {{ strtolower($f24->due_date->locale('it')->isoFormat('DD MMM YYYY')) }}
                            </span>
                        @else
                            <span class="text-gray-400">–</span>
                        @endif
                    </td>
                    
                    <!-- Data importazione -->
                    <td class="whitespace-nowrap py-4 px-4 text-gray-700">
                        {{ strtolower($f24->imported_at->locale('it')->isoFormat('DD MMM YYYY')) }}
                    </td>
                    
                    <!-- Azioni -->
                    <td class="whitespace-nowrap py-4 px-4 text-right">
                        <div class="flex justify-end items-center gap-x-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200 relative">                        
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="p-1 hover:bg-gray-100 rounded">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="0.392857" y="0.392857" width="19.2143" height="19.2143" rx="2.75" stroke="#AD96FF" stroke-width="0.785714"/>
                                        <path d="M5.74408 10H5.75063M10 10H10.0066M14.2494 10H14.256" stroke="#AD96FF" stroke-width="2.35714" stroke-linecap="round"/>
                                    </svg>
                                </button>
                                
                                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border">
                                    <div class="py-1">
                                        <a href="{{ route('f24.show', $f24) }}" 
                                           class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <div class="flex items-center gap-2">
                                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M8 2a6 6 0 1 0 0 12A6 6 0 0 0 8 2ZM1 8a7 7 0 1 1 14 0A7 7 0 0 1 1 8Z" stroke="currentColor" stroke-width="1"/>
                                                    <path d="M8 5v3l2 2" stroke="currentColor" stroke-width="1" stroke-linecap="round"/>
                                                </svg>
                                                Visualizza dettagli
                                            </div>
                                        </a>
                                        
                                        <button wire:click="showTaxesForF24('{{ $f24->id }}')" 
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <div class="flex items-center gap-2">
                                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M4 2.5A1.5 1.5 0 0 1 5.5 1h5A1.5 1.5 0 0 1 12 2.5v11a1.5 1.5 0 0 1-1.5 1.5h-5A1.5 1.5 0 0 1 4 13.5v-11z" stroke="currentColor" stroke-width="1.5"/>
                                                    <path d="M9 4.5h1.5M9 6.5h1.5M9 8.5h1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                </svg>
                                                Visualizza tasse
                                            </div>
                                        </button>
                                        
                                        <a href="{{ route('f24.download', $f24) }}" 
                                           target="_blank"
                                           class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <div class="flex items-center gap-2">
                                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M4 2.5A1.5 1.5 0 0 1 5.5 1h5A1.5 1.5 0 0 1 12 2.5v11a1.5 1.5 0 0 1-1.5 1.5h-5A1.5 1.5 0 0 1 4 13.5v-11z" stroke="currentColor" stroke-width="1.5"/>
                                                    <path d="M9 4.5h1.5M9 6.5h1.5M9 8.5h1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                </svg>
                                                Visualizza PDF
                                            </div>
                                        </a>
                                        
                                        @if($f24->payment_status === 'PENDING' || $f24->payment_status === 'PARTIALLY_PAID')
                                            <button onclick="confirmMarkF24AsPaid('{{ $f24->id }}', '{{ $f24->filename }}', {{ $f24->total_amount }})" 
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <div class="flex items-center gap-2">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="m13.854 3.646-10 10a.5.5 0 0 1-.708-.708l10-10a.5.5 0 0 1 .708.708ZM4 1a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM2.5 4a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm8.5 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM9.5 12a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" stroke="currentColor" stroke-width="1"/>
                                                    </svg>
                                                    Marca come pagato
                                                </div>
                                            </button>
                                        @endif
                                        
                                        <!-- Gestione Ricevuta -->
                                        @if($f24->hasReceipt())
                                            <a href="{{ route('f24.download-receipt', $f24) }}" 
                                               target="_blank"
                                               class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <div class="flex items-center gap-2">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z" fill="currentColor"/>
                                                    </svg>
                                                    Visualizza ricevuta
                                                </div>
                                            </a>
                                            
                                            <button onclick="openReceiptModal('{{ $f24->id }}', '{{ $f24->receipt_filename }}', true)" 
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <div class="flex items-center gap-2">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M8 3a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5H6a.5.5 0 0 1 0-1h1.5V3.5A.5.5 0 0 1 8 3zM3.732 4h.732a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5H3.732a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5zM2 6.5a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H2.5a.5.5 0 0 1-.5-.5z" fill="currentColor"/>
                                                    </svg>
                                                    Sostituisci ricevuta
                                                </div>
                                            </button>
                                            
                                            <button onclick="deleteReceipt('{{ $f24->id }}')" 
                                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                <div class="flex items-center gap-2">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 1 0v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5z" fill="currentColor"/>
                                                        <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" fill="currentColor"/>
                                                    </svg>
                                                    Elimina ricevuta
                                                </div>
                                            </button>
                                        @else
                                            <button onclick="openReceiptModal('{{ $f24->id }}', null, false)" 
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <div class="flex items-center gap-2">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z" fill="currentColor"/>
                                                        <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z" fill="currentColor"/>
                                                    </svg>
                                                    Carica ricevuta
                                                </div>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $f24s->links() }}
        </div>
    </div>

    <!-- Modal Ricevuta -->
<div id="receiptModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="receiptModalTitle">Carica Ricevuta</h3>
                <button onclick="closeReceiptModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="receiptModalContent">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Seleziona file</label>
                    <input type="file" id="receiptFile" accept=".pdf,.jpg,.jpeg,.png" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <p class="text-xs text-gray-500 mt-1">Formati supportati: PDF, JPG, PNG (max 10MB)</p>
                </div>
                
                <div id="currentReceiptInfo" class="mb-4 p-3 bg-blue-50 rounded-md hidden">
                    <p class="text-sm text-blue-800">
                        <strong>Ricevuta attuale:</strong> <span id="currentReceiptName"></span>
                    </p>
                    <p class="text-xs text-blue-600 mt-1">Il nuovo file sostituirà quello esistente</p>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button onclick="closeReceiptModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                    Annulla
                </button>
                <button onclick="uploadReceipt()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Carica
                </button>
            </div>
        </div>
    </div>
</div>

<!-- F24 Import Modal -->
<div id="f24ImportModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-900">Importa F24</h2>
                    <button onclick="closeF24ImportModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="f24ImportForm" class="space-y-6">
                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Carica F24 (PDF o immagini)
                        </label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                            <input type="file" id="f24Files" multiple accept=".pdf,.jpg,.jpeg,.png" class="hidden">
                            <div id="dropZone" onclick="document.getElementById('f24Files').click()" class="cursor-pointer">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-600">Clicca per selezionare o trascina qui i file F24</p>
                                <p class="text-xs text-gray-500">PDF, JPG, PNG (max 10 file)</p>
                            </div>
                        </div>
                        <div id="fileList" class="mt-4 space-y-2"></div>
                    </div>
                    
                    <!-- Payment Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Stato dei pagamenti
                        </label>
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input type="radio" name="paymentStatus" value="paid" class="mr-3">
                                <span class="text-sm">F24 già pagati - Importa come PAID</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="paymentStatus" value="pending" checked class="mr-3">
                                <span class="text-sm">F24 da pagare - Importa come PENDING</span>
                            </label>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" id="skipDuplicates" class="mr-3">
                            <span class="text-sm">Salta duplicati</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" id="autoRecalculate" class="mr-3">
                            <span class="text-sm">Ricalcola automaticamente i bollettini</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeF24ImportModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Annulla
                        </button>
                        <button type="button" onclick="submitF24Import()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Importa F24
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        let currentF24Id = null;
        let isReplacing = false;

        function openReceiptModal(f24Id, currentFilename, replacing) {
            console.log('openReceiptModal chiamata con:', { f24Id, currentFilename, replacing });
            
            currentF24Id = f24Id;
            isReplacing = replacing;
            
            const modal = document.getElementById('receiptModal');
            const title = document.getElementById('receiptModalTitle');
            const currentInfo = document.getElementById('currentReceiptInfo');
            const currentName = document.getElementById('currentReceiptName');
            
            console.log('Elementi trovati:', { modal, title, currentInfo, currentName });
            
            if (!modal) {
                console.error('Modal non trovato!');
                alert('Errore: Modal non trovato');
                return;
            }
            
            if (replacing && currentFilename) {
                title.textContent = 'Sostituisci Ricevuta';
                currentInfo.classList.remove('hidden');
                currentName.textContent = currentFilename;
            } else {
                title.textContent = 'Carica Ricevuta';
                currentInfo.classList.add('hidden');
            }
            
            modal.classList.remove('hidden');
            console.log('Modal aperto');
        }

        function closeReceiptModal() {
            const modal = document.getElementById('receiptModal');
            const fileInput = document.getElementById('receiptFile');
            
            modal.classList.add('hidden');
            fileInput.value = '';
            currentF24Id = null;
            isReplacing = false;
        }

        function uploadReceipt() {
            const fileInput = document.getElementById('receiptFile');
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Seleziona un file');
                return;
            }
            
            if (file.size > 10 * 1024 * 1024) { // 10MB
                alert('Il file è troppo grande. Dimensione massima: 10MB');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const base64Content = e.target.result.split(',')[1];
                
                @this.uploadF24Receipt(currentF24Id, {
                    name: file.name,
                    content: base64Content
                }).then(result => {
                    if (result.success) {
                        closeReceiptModal();
                        // Ricarica la pagina per aggiornare i badge
                        window.location.reload();
                    }
                });
            };
            
            reader.readAsDataURL(file);
        }

        function deleteReceipt(f24Id) {
            if (confirm('Sei sicuro di voler eliminare la ricevuta?')) {
                @this.deleteF24Receipt(f24Id).then(result => {
                    if (result) {
                        // Ricarica la pagina per aggiornare i badge
                        window.location.reload();
                    }
                });
            }
        }

        function confirmMarkF24AsPaid(f24Id, f24Filename, totalAmount) {
            Swal.fire({
                title: 'Marca F24 come pagato',
                html: `<div class="text-left space-y-4"><div class="bg-gray-50 p-3 rounded"><p><strong>F24:</strong> ${f24Filename}</p><p><strong>Importo totale:</strong> €${totalAmount.toFixed(2)}</p></div><div><label class="block font-semibold mb-1">Data pagamento:</label><input type="date" id="f24PaymentDate" class="w-full border rounded px-3 py-2" value="${new Date().toISOString().split('T')[0]}"></div><div><label class="block font-semibold mb-1">Riferimento pagamento (opzionale):</label><input type="text" id="f24PaymentReference" class="w-full border rounded px-3 py-2" placeholder="Es: F24, CRO bancario, etc."></div></div>`,
                icon: 'question',
                width: '500px',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Marca come pagato',
                cancelButtonText: 'Annulla',
                preConfirm: () => {
                    const paymentDate = document.getElementById('f24PaymentDate').value;
                    const reference = document.getElementById('f24PaymentReference').value;
                    
                    // Se non c'è data, usa quella di oggi
                    const finalDate = paymentDate || new Date().toISOString().split('T')[0];
                    
                    return {
                        paymentDate: finalDate,
                        reference: reference
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const paymentData = result.value;
                    @this.markF24AsPaid(f24Id, paymentData);
                }
            });
        }

        // F24 Import Modal JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const f24ImportBtn = document.getElementById('f24ImportBtn');
            const f24ImportModal = document.getElementById('f24ImportModal');
            
            if (f24ImportBtn) {
                f24ImportBtn.addEventListener('click', function() {
                    if (f24ImportModal) {
                        f24ImportModal.classList.remove('hidden');
                    }
                });
            }
        });

        function closeF24ImportModal() {
            const modal = document.getElementById('f24ImportModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }



        async function submitF24Import() {
            const files = document.getElementById('f24Files').files;
            const paymentStatus = document.querySelector('input[name="paymentStatus"]:checked')?.value || 'pending';
            const skipDuplicates = document.getElementById('skipDuplicates').checked;
            const autoRecalculate = document.getElementById('autoRecalculate').checked;
            
            if (files.length === 0) {
                alert('Seleziona almeno un file F24');
                return;
            }
            
            // Converti i file in base64
            const processedFiles = [];
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const base64Content = await fileToBase64(file);
                processedFiles.push({
                    name: file.name,
                    mime_type: file.type,
                    content: base64Content,
                    size: file.size
                });
            }
            
            // Chiama il metodo Livewire
            @this.importF24({
                files: processedFiles,
                payment_status: paymentStatus,
                skip_duplicates: skipDuplicates,
                auto_recalculate: autoRecalculate
            });
            
            closeF24ImportModal();
        }
        
        function fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => {
                    // Rimuovi il prefisso "data:mime/type;base64,"
                    const base64 = reader.result.split(',')[1];
                    resolve(base64);
                };
                reader.onerror = error => reject(error);
            });
        }
    </script>
</div>
