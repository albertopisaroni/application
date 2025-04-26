<x-app-layout>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Registrazioni</h1>

        <form method="GET" class="flex space-x-4 mb-6">
            <select name="project_type" class="border px-2 py-1">
                <option value="">Tutti i tipi</option>
                <option value="Apertura partita iva" {{ request('project_type') == 'Apertura partita iva' ? 'selected' : '' }}>Apertura partita iva</option>
                <option value="Partita iva esistente" {{ request('project_type') == 'Partita iva esistente' ? 'selected' : '' }}>Partita iva esistente</option>
                <option value="Voglio solo delle informazioni" {{ request('project_type') == 'Voglio solo delle informazioni' ? 'selected' : '' }}>Voglio solo delle informazioni</option>
            </select>

            <select name="contacted" class="border px-2 py-1">
                <option value="">Contatto</option>
                <option value="1" {{ request('contacted') == '1' ? 'selected' : '' }}>Contattato</option>
                <option value="0" {{ request('contacted') === '0' ? 'selected' : '' }}>Da contattare</option>
            </select>

            <select name="registered" class="border px-2 py-1">
                <option value="">Registrazione</option>
                <option value="1" {{ request('registered') == '1' ? 'selected' : '' }}>Registrato</option>
                <option value="0" {{ request('registered') === '0' ? 'selected' : '' }}>Non registrato</option>
            </select>

            <button class="bg-indigo-600 text-white px-4 py-1 rounded">Filtra</button>
        </form>

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-sm text-gray-700 uppercase tracking-wider">
                <th class="px-4 py-2 border">
                    <a href="{{ route('app.admin.registrations.index', ['sort_by' => 'behavior_profile', 'direction' => $sortBy === 'behavior_profile' && $direction === 'asc' ? 'desc' : 'asc']) }}">
                        Profilo @if($sortBy === 'behavior_profile') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                    </a>
                </th>
                <th class="px-4 py-2 border">
                    <a href="{{ route('app.admin.registrations.index', ['sort_by' => 'contacted', 'direction' => $sortBy === 'contacted' && $direction === 'asc' ? 'desc' : 'asc']) }}">
                        Contattato @if($sortBy === 'contacted') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                    </a>
                </th>
                <th class="px-4 py-2 border">
                    <a href="{{ route('app.admin.registrations.index', ['sort_by' => 'project_type', 'direction' => $sortBy === 'project_type' && $direction === 'asc' ? 'desc' : 'asc']) }}">
                        Tipo @if($sortBy === 'project_type') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                    </a>
                </th>
                <th class="px-4 py-2 border">
                    <a href="{{ route('app.admin.registrations.index', ['sort_by' => 'fullname', 'direction' => $sortBy === 'fullname' && $direction === 'asc' ? 'desc' : 'asc']) }}">
                        Nome @if($sortBy === 'fullname') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                    </a>
                </th>
                <th class="px-4 py-2 border">
                    <a href="{{ route('app.admin.registrations.index', ['sort_by' => 'email', 'direction' => $sortBy === 'email' && $direction === 'asc' ? 'desc' : 'asc']) }}">
                        Email @if($sortBy === 'email') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                    </a>
                </th>
                <th class="px-4 py-2 border">
                    <a href="{{ route('app.admin.registrations.index', ['sort_by' => 'phone', 'direction' => $sortBy === 'phone' && $direction === 'asc' ? 'desc' : 'asc']) }}">
                        Telefono @if($sortBy === 'phone') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                    </a>
                </th>
                <th class="px-4 py-2 border">
                    <a href="{{ route('app.admin.registrations.index', ['sort_by' => 'registered', 'direction' => $sortBy === 'registered' && $direction === 'asc' ? 'desc' : 'asc']) }}">
                        Stato @if($sortBy === 'registered') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                    </a>
                </th>
                <th class="px-4 py-2 border">
                    <a href="{{ route('app.admin.registrations.index', ['sort_by' => 'created_at', 'direction' => $sortBy === 'created_at' && $direction === 'asc' ? 'desc' : 'asc']) }}">
                        Creato il @if($sortBy === 'created_at') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                    </a>
                </th>
                    <th class="px-4 py-2 border"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($registrations as $registration)
                <tr class="text-sm">
                    <td class="px-4 py-2 border">{{ $registration->behavior_profile }}</td>
                    <td class="px-4 py-2 border">{{ $registration->contacted ? '✅ Contattato' : '‼️ Da contattare' }}</td>
                    <td class="px-4 py-2 border">{{ $registration->project_type }}</td>
                    <td class="px-4 py-2 border">{{ $registration->fullname }}</td>
                    <td class="px-4 py-2 border">{{ $registration->email }}</td>
                    <td class="px-4 py-2 border">{{ $registration->phone }}</td>
                    <td class="px-4 py-2 border font-semibold">{{ $registration->registered ? '🚀 Registrato' : '⚠️ Non registrato' }}</td>
                    <td class="px-4 py-2 border">{{ $registration->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2 border">
                        <a href="{{ route('app.admin.registrations.show', $registration) }}" class="text-indigo-600 underline">Dettagli</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-6">
            {{ $registrations->links() }}
        </div>
    </div>

</x-app-layout>