<x-app-layout>

    <div class="px-2">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-normal">Registrazioni</h1>

            <form method="GET" class="flex items-center gap-x-4">
                @if (request('project_type') || request('contacted') || request('registered'))
                <button type="button" onclick="resetFilters()" class="items-center gap-x-2 flex bg-[#e8e8e8] pr-4 pl-3 py-2 text-sm rounded-[4px] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>                      
                    Elimina filtri
                </button>
                @endif

                <select name="project_type" class="text-[#050505] invalid:text-[#aba7af] border border-[#e8e8e8] rounded px-3 py-2 text-sm pr-8">
                    <option value="">Tutti i tipi</option>
                    <option value="Apertura partita iva" {{ request('project_type') == 'Apertura partita iva' ? 'selected' : '' }}>Apertura partita iva</option>
                    <option value="Partita iva esistente" {{ request('project_type') == 'Partita iva esistente' ? 'selected' : '' }}>Partita iva esistente</option>
                    <option value="Voglio solo delle informazioni" {{ request('project_type') == 'Voglio solo delle informazioni' ? 'selected' : '' }}>Voglio solo delle informazioni</option>
                </select>

                <select name="contacted" class="text-[#050505] invalid:text-[#aba7af] border border-[#e8e8e8] rounded px-3 py-2 text-sm pr-8">
                    <option value="">Contatto</option>
                    <option value="1" {{ request('contacted') == '1' ? 'selected' : '' }}>Contattato</option>
                    <option value="0" {{ request('contacted') === '0' ? 'selected' : '' }}>Da contattare</option>
                </select>

                <select name="registered" class="text-[#050505] invalid:text-[#aba7af] border border-[#e8e8e8] rounded px-3 py-2 text-sm pr-8">
                    <option value="">Registrazione</option>
                    <option value="1" {{ request('registered') == '1' ? 'selected' : '' }}>Registrato</option>
                    <option value="0" {{ request('registered') === '0' ? 'selected' : '' }}>Non registrato</option>
                </select>

                <button type="submit" class="bg-black text-white px-4 py-2 text-sm rounded-[4px] transition">
                    Filtra
                </button>
            </form>
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
            <input type="text" placeholder="Cerca per nome, email o telefono" class="bg-[#f5f5f5] rounded-[4px] border-0 ring-0 focus:ring-0 pr-4 py-2 w-full text-sm">
            <div class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400 bg-white border border-gray-300 rounded px-1 py-0.5 pointer-events-none">
                ⌘K
            </div>
        </div>

        <div class="mb-3 text-sm text-gray-600 flex items-center gap-x-8">
            <button class="text-[#FC460E] hover:font-semibold focus:outline-none font-semibold">
                Da contattare: {{ $registrations->where('contacted', false)->count() }}
            </button>
        
            <button class="hover:font-semibold focus:outline-none">
                Contattati: {{ $registrations->where('contacted', true)->count() }}
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
                Ci sono <strong>{{ $registrations->where('contacted', false)->count() }} registrazioni</strong> da contattare e <strong>{{ $registrations->where('registered', true)->count() }} registrati</strong>
            </div>
        </div>

        <div class="">
            <table class="w-full text-sm text-left border-collapse">
                <thead>
                    <tr class="text-[#616161] text-xs border-b">
                        <th class="py-2 pl-2 pr-4 font-normal">
                            <a href="{{ route('admin.registrations.index', ['sort_by' => 'fullname', 'direction' => $sortBy === 'fullname' && $direction === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-900">
                                Nome @if($sortBy === 'fullname') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                            </a>
                        </th>
                        <th class="py-2 px-4 font-normal">
                            <a href="{{ route('admin.registrations.index', ['sort_by' => 'behavior_profile', 'direction' => $sortBy === 'behavior_profile' && $direction === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-900">
                                Profilo @if($sortBy === 'behavior_profile') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                            </a>
                        </th>
                        <th class="py-2 px-4 font-normal">
                            <a href="{{ route('admin.registrations.index', ['sort_by' => 'project_type', 'direction' => $sortBy === 'project_type' && $direction === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-900">
                                Tipo @if($sortBy === 'project_type') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                            </a>
                        </th>
                        <th class="py-2 px-4 font-normal">
                            <a href="{{ route('admin.registrations.index', ['sort_by' => 'contacted', 'direction' => $sortBy === 'contacted' && $direction === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-900">
                                Contattato @if($sortBy === 'contacted') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                            </a>
                        </th>
                        <th class="py-2 px-4 font-normal">
                            <a href="{{ route('admin.registrations.index', ['sort_by' => 'registered', 'direction' => $sortBy === 'registered' && $direction === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-900">
                                Stato @if($sortBy === 'registered') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                            </a>
                        </th>
                        <th class="py-2 px-4 font-normal">
                            <a href="{{ route('admin.registrations.index', ['sort_by' => 'created_at', 'direction' => $sortBy === 'created_at' && $direction === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-900">
                                Creato il @if($sortBy === 'created_at') ({{ $direction === 'asc' ? '▲' : '▼' }}) @endif
                            </a>
                        </th>
                        <th class="py-2 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($registrations as $registration)
                    <tr class="hover:bg-[#f5f5f5] bg-white group transition-all duration-200">
                        <td class="whitespace-nowrap py-4 pl-2 pr-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-xs font-medium text-gray-600">{{ substr($registration->fullname, 0, 2) }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                  <div class="font-normal text-gray-900 truncate">
                                    {{ $registration->fullname }}
                                  </div>
                                  <div class="text-xs text-[#616161] lowercase truncate">
                                    {{ $registration->email }}
                                  </div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap py-4 px-4 font-normal text-gray-800">
                            {{ $registration->behavior_profile }}
                        </td>
                        <td class="whitespace-nowrap py-4 px-4 font-normal text-gray-800">
                            {{ $registration->project_type }}
                        </td>
                        <td class="py-4 px-4 whitespace-nowrap">  
                            @if ($registration->contacted)
                                <span class="bg-[#cff5d4] text-[#3aab53] border-[#3aab53] border text-xs font-medium px-3 py-1 rounded-full">
                                    ✅ Contattato
                                </span>
                            @else
                                <span class="bg-[#fff4f0] text-[#FC460E] text-xs font-medium px-3 py-1 rounded-[4px]">
                                    ‼️ Da contattare
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-4 whitespace-nowrap">
                            @if ($registration->registered)
                                <span class="bg-[#cff5d4] text-[#3aab53] border-[#3aab53] border text-xs font-medium px-3 py-1 rounded-full">
                                    🚀 Registrato
                                </span>
                            @else
                                <span class="bg-[#fff4f0] text-[#FC460E] text-xs font-medium px-3 py-1 rounded-[4px]">
                                    ⚠️ Non registrato
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap py-4 px-4 text-gray-700">
                            {{ $registration->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="whitespace-nowrap py-4 px-4 text-right">
                            <div class="flex justify-end items-center gap-x-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <a href="{{ route('admin.registrations.show', $registration) }}" class="text-indigo-600 hover:text-indigo-800">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="0.392857" y="0.392857" width="19.2143" height="19.2143" rx="2.75" stroke="#AD96FF" stroke-width="0.785714"/>
                                        <path d="M5.74408 10H5.75063M10 10H10.0066M14.2494 10H14.256" stroke="#AD96FF" stroke-width="2.35714" stroke-linecap="round"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $registrations->links() }}
            </div>
        </div>
    </div>

    <script>
        function resetFilters() {
            window.location.href = '{{ route("admin.registrations.index") }}';
        }
    </script>

</x-app-layout>