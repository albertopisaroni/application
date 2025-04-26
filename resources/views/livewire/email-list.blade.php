<div class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar: cartelle -->
    <aside class="w-64 bg-white shadow-md flex-shrink-0">
        <div class="p-4 font-bold text-lg border-b">📬 Webmail</div>
        <nav class="p-4 space-y-2">
            @foreach($folders as $folder)
                <button 
                    wire:click="$set('currentFolder', '{{ $folder->name }}')" 
                    class="block w-full text-left px-3 py-2 rounded hover:bg-gray-100 {{ $currentFolder === $folder->name ? 'bg-gray-200 font-semibold' : '' }}">
                    {{ $folder->name }}
                </button>
            @endforeach
        </nav>
    </aside>

    <!-- Main email section -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Email list -->
        <div class="w-1/2 border-r overflow-y-auto flex flex-col">
            <!-- Topbar -->
            <header class="bg-white shadow p-4 flex justify-between items-center">
                <h2 class="text-xl font-semibold">📁 {{ $currentFolder }}</h2>
                <input
                    type="text"
                    wire:model.debounce.live.500ms="search"
                    placeholder="Cerca per oggetto o mittente..."
                    class="ml-4 w-64 border rounded px-3 py-2 text-sm"
                />
            </header>

            <main class="flex-1 overflow-y-auto p-4 space-y-4" wire:poll.visible.30s>
                @foreach ($messages as $message)
                    <div 
                        wire:click="openEmail({{ $message['uid'] }})"
                        class="bg-white rounded shadow p-4 hover:bg-blue-50 cursor-pointer transition {{ $message['seen'] ? 'text-gray-500' : 'text-black font-semibold' }}"
                    >
                        <div class="text-xs text-gray-500">{{ $message['date'] ?? '' }}</div>
                        <div class="font-semibold truncate">{{ $message['subject'] }}</div>
                        <div class="text-sm text-gray-600 truncate">{{ $message['from'] ?? '' }}</div>
                    </div>
                @endforeach

                <div class="mt-4">
                    {{ $messages->links() }}
                </div>
            </main>
        </div>

        <!-- Email preview -->
        <div class="w-1/2 overflow-y-auto p-6" wire:loading.class="opacity-50">
            @if ($openedEmail)
                <div class="bg-white p-6 rounded shadow space-y-4">
                    <div class="text-xs text-gray-500">{{ $openedEmail['date'] }}</div>
                    <div class="text-xl font-bold">{{ $openedEmail['subject'] }}</div>
                    <div class="text-sm text-gray-600">Da: {{ $openedEmail['from'] }}</div>
                    <hr>
                    <div class="prose max-w-none">{!! $openedEmail['body'] !!}</div>
                    <div class="pt-4">
                        <button 
                            wire:click="$set('openedEmail', null)" 
                            class="text-blue-500 hover:underline text-sm"
                        >
                            ← Torna alla lista
                        </button>
                    </div>
                </div>
            @else
                <div class="text-gray-500 text-center mt-20">📨 Seleziona un’email per leggerla</div>
            @endif
        </div>
    </div>
</div>