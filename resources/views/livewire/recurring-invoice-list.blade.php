<div class="px-2">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-normal">Fatture Ricorrenti</h1>

        <div class="flex items-center gap-x-4">
            @if ($search || $statusFilter !== 'all')
                <button wire:click="resetFilters" class="items-center gap-x-2 flex bg-[#e8e8e8] pr-4 pl-3 py-2 text-sm rounded-[4px] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>                      
                    Elimina filtri
                </button>
            @endif

            <select wire:model.live="statusFilter" class="text-[#050505] border border-[#e8e8e8] rounded px-3 py-2 text-sm pr-8">
                <option value="all">Tutti gli stati</option>
                <option value="active">Attive</option>
                <option value="inactive">Inattive</option>
            </select>

            <a href="{{ route('fatture-ricorrenti.nuova') }}" wire:navigate class="items-center gap-x-2 flex bg-black text-white px-4 py-2 text-sm rounded-[4px] transition">
                <svg width="11" height="17" viewBox="0 0 11 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_569_5702)">
                    <path d="M6.50062 0.5C6.67973 0.586454 6.79013 0.711577 6.75418 0.923154L6.02499 6.493H10.5442C10.675 6.493 10.8805 6.84542 10.7466 6.97557L5.44883 16.3243C5.18286 16.694 4.733 16.4283 4.81858 16.014L5.39092 10.5092H0.93466C0.832853 10.5092 0.620968 10.214 0.704004 10.0926L6.06285 0.772881C6.13188 0.649959 6.21269 0.560675 6.34218 0.5H6.50062Z" fill="white"/>
                    </g>
                    <defs>
                    <clipPath id="clip0_569_5702">
                    <rect width="10.1053" height="16" fill="white" transform="translate(0.684784 0.5)"/>
                    </clipPath>
                    </defs>
                </svg>
                Crea fattura ricorrente
            </a>
        </div>
    </div>

    <div class="relative flex mb-6 bg-[#f5f5f5] rounded-[4px] items-center pl-4 pr-2">
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M11.8875 11.2198L8.775 8.09977C9.4425 7.25977 9.84 6.20227 9.84 5.05477C9.84 2.33227 7.635 0.134766 4.92 0.134766C2.205 0.134766 0 2.33227 0 5.05477C0 7.77727 2.205 9.97477 4.92 9.97477C6.21 9.97477 7.38 9.47977 8.2575 8.66227L11.355 11.7523C11.505 11.9023 11.745 11.9023 11.8875 11.7523C12.0375 11.6098 12.0375 11.3698 11.8875 11.2198ZM4.92 9.21727C2.6175 9.21727 0.7575 7.34977 0.7575 5.05477C0.7575 2.75977 2.6175 0.892266 4.92 0.892266C7.2225 0.892266 9.0825 2.75227 9.0825 5.05477C9.0825 7.35727 7.2225 9.21727 4.92 9.21727Z" fill="#050505"/>
        </svg>
        <input wire:model.debounce.live.500ms="search" type="text" placeholder="Cerca per nome cliente o template" class="bg-[#f5f5f5] rounded-[4px] border-0 ring-0 focus:ring-0 pr-4 py-2 w-full text-sm">
    </div>

    @if (session()->has('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-3 text-sm text-gray-600 flex items-center gap-x-8">
        <button wire:click="$set('statusFilter', 'active')" class="text-[#10b981] hover:font-semibold focus:outline-none {{ $statusFilter === 'active' ? 'font-semibold' : '' }}">
            Attive: {{ $activeCount }}
        </button>
        <button wire:click="$set('statusFilter', 'inactive')" class="text-[#ef4444] hover:font-semibold focus:outline-none {{ $statusFilter === 'inactive' ? 'font-semibold' : '' }}">
            Inattive: {{ $inactiveCount }}
        </button>
    </div>

    <div class="">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="text-[#616161] text-xs border-b">
                    <th class="py-2 pl-2 pr-4 font-normal">Template</th>
                    <th class="py-2 px-4 font-normal">Cliente</th>
                    <th class="py-2 px-4 font-normal">Ricorrenza</th>
                    <th class="py-2 px-4 font-normal">Stripe</th>
                    <th class="py-2 px-4 font-normal">Stato</th>
                    <th class="py-2 px-4 font-normal">Importo</th>
                    <th class="py-2 px-4 font-normal">Prossima fattura</th>
                    <th class="py-2 px-4 font-normal">Generate</th>
                    <th class="py-2 px-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($recurringInvoices as $recurringInvoice)
                    <tr class="hover:bg-[#f5f5f5] bg-white group transition-all duration-200">
                        <td class="whitespace-nowrap py-4 pl-2 pr-4">
                            <div class="font-normal text-gray-900">
                                {{ $recurringInvoice->template_name ?: 'Template #' . $recurringInvoice->id }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap py-4 px-4">
                            <div class="font-normal text-gray-900">
                                {{ $recurringInvoice->client->name ?? 'Cliente non trovato' }}
                            </div>
                        </td>
                        <td class="py-4 px-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ $recurringInvoice->recurrence_description }}
                            </div>
                        </td>
                        <td class="py-4 px-4 whitespace-nowrap">
                            @if($recurringInvoice->stripe_subscription_id)
                                <div class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"/>
                                    </svg>
                                    @if($recurringInvoice->trigger_on_payment)
                                        <span class="text-xs text-blue-600 font-medium">Auto</span>
                                    @else
                                        <span class="text-xs text-gray-500">Collegato</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 truncate max-w-20" title="{{ $recurringInvoice->stripe_subscription_id }}">
                                    {{ substr($recurringInvoice->stripe_subscription_id, 0, 12) }}...
                                </div>
                            @else
                                <span class="text-xs text-gray-400">–</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 whitespace-nowrap">
                            @if ($recurringInvoice->is_active)
                                <span class="bg-[#edffef] text-[#1b7101] text-xs font-medium px-3 py-1 rounded-[4px]">
                                    Attiva
                                </span>
                            @else
                                <span class="bg-[#fff4f0] text-[#FC460E] text-xs font-medium px-3 py-1 rounded-[4px]">
                                    Inattiva
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap py-4 px-4 text-gray-900 font-medium">
                            {{ number_format($recurringInvoice->total, 2, ',', '.') }} EUR
                        </td>
                        <td class="whitespace-nowrap py-4 px-4 text-gray-700">
                            @if($recurringInvoice->is_active)
                                {{ strtolower($recurringInvoice->next_invoice_date->locale('it')->isoFormat('DD MMM YYYY')) }}
                            @else
                                <span class="text-gray-400">–</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap py-4 px-4 text-gray-700">
                            {{ $recurringInvoice->invoices_generated }}
                            @if($recurringInvoice->max_invoices)
                                / {{ $recurringInvoice->max_invoices }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap py-4 px-4 text-right">
                            <div class="flex justify-end items-center gap-x-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <button wire:click="toggleActive({{ $recurringInvoice->id }})" class="p-1 hover:bg-gray-100 rounded text-sm">
                                    @if($recurringInvoice->is_active)
                                        Disattiva
                                    @else
                                        Attiva
                                    @endif
                                </button>
                                <button wire:click="delete({{ $recurringInvoice->id }})" 
                                        onclick="return confirm('Sei sicuro di voler eliminare questa fattura ricorrente?')" 
                                        class="p-1 hover:bg-gray-100 rounded text-sm text-red-600">
                                    Elimina
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-gray-500">
                            Nessuna fattura ricorrente trovata.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $recurringInvoices->links() }}
        </div>
    </div>
</div>