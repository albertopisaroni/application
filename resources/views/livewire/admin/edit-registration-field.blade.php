<div wire:poll.3s>
    @if ($field === 'registered' || $field === 'contacted' || $field === 'form_autofill_fullname' || $field === 'form_autofill_email' || $field === 'form_autofill_phone')
        @if ($editing)
            <input type="checkbox" wire:model.defer="value" class="mr-2" />
            <button wire:click="save" class="text-green-600 text-sm">💾</button>
        @else
            <span>{{ $value ? '✅' : '❌' }}</span>
            @if ($field === 'registered' || $field === 'contacted')
                <button wire:click="$set('editing', true)" class="text-blue-600 underline text-sm ml-2">✎</button>
            @endif
        @endif
    @elseif ($field === 'document_front' || $field === 'document_back')
        @if ($editing)
            <input type="file" wire:model="value" class="text-sm">
            @error('value') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            
            @if ($value instanceof \Illuminate\Http\UploadedFile)
                <div class="mt-1 flex items-center space-x-2">
                    <span class="text-xs text-gray-700 truncate max-w-xs">
                        {{ $value->getClientOriginalName() }}
                    </span>
                    <button wire:click="save" class="text-blue-600 underline text-sm">💾 Salva</button>
                </div>
            @endif
        @else
            @if ($registration->{$field})
                <a href="{{ Storage::disk('s3')->temporaryUrl($registration->{$field}, now()->addMinutes(10)) }}"
                target="_blank" class="text-blue-600 underline text-sm mr-2">📄 Visualizza</a>
            @else
                <span class="text-sm text-gray-500">Nessun file caricato</span>
            @endif
            <button wire:click="$set('editing', true)" class="text-blue-600 underline text-sm ml-2">✎</button>
        @endif
    @elseif (in_array($field, [
        'step_history', 'location', 'label',
        'utm_source', 'utm_medium', 'utm_campaign',
        'utm_content', 'ab_variant', 'signed_at', 'updated_at', 'created_at', 'id',
        'page_time',
        'scroll_time',
        'scroll_bounce',
        'mouse_movement',
        'form_time_fullname',
        'form_time_email',
        'form_time_phone',
        'form_autofill_fullname',
        'form_autofill_email',
        'form_autofill_phone',
        'section_time_fatture_e_pagamenti',
        'section_time_flussi_di_lavoro',
        'section_time_tasse_e_scadenze',
        'section_time_il_ai_automazioni_intelligenti',
        'section_time_il_nostro_team_e_qui_per_te',
        'section_time_con_noi_essere_freelance',
        'section_time_newo_e_pensato_per_farti_crescere',
        'section_time_newo_e_gia_la_scelta',
        'behavior_score',
    ]))
        {{ $value }}
    @else
        @if ($editing)
            <input type="text" wire:model.defer="value" class="text-sm border px-1 py-0.5" wire:keydown.enter="save" wire:blur="save" />
            <button wire:click="save" class="text-blue-600 underline text-sm">💾</button>
        @else
            <span>{{ $value }}</span>
            <button wire:click="$set('editing', true)" class="text-blue-600 underline text-sm ml-2">✎</button>
        @endif
    @endif
</div>