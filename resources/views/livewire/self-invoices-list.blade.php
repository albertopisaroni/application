<div class="px-2">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-normal">Autofatture</h1>

        <div class="flex items-center gap-x-4">

        <livewire:fiscoapi-session-button />


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
                @foreach ($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>

            <button class="size-9 bg-[#e8e8e8] rounded-[4px] flex items-center justify-center">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <mask id="mask0_913_6687" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="17" height="16">
                    <path d="M0.790039 0H16.79V16H0.790039V0Z" fill="white"/>
                    </mask>
                    <g mask="url(#mask0_913_6687)">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12.795 7.995C12.795 9.05454 12.3741 10.0707 11.6249 10.8199C10.8757 11.5691 9.85958 11.99 8.80004 11.99C7.7405 11.99 6.72435 11.5691 5.97515 10.8199C5.22594 10.0707 4.80504 9.05454 4.80504 7.995C4.80504 6.93546 5.22594 5.91932 5.97515 5.17011C6.72435 4.4209 7.7405 4 8.80004 4C9.85958 4 10.8757 4.4209 11.6249 5.17011C12.3741 5.91932 12.795 6.93546 12.795 7.995ZM11.796 7.995C11.7967 8.38872 11.7197 8.7787 11.5694 9.1426C11.4191 9.5065 11.1984 9.83717 10.9201 10.1157C10.6418 10.3942 10.3113 10.615 9.94749 10.7656C9.58369 10.9161 9.19376 10.9934 8.80004 10.993C8.40632 10.9934 8.01639 10.9161 7.65259 10.7656C7.28879 10.615 6.95827 10.3942 6.67996 10.1157C6.40165 9.83717 6.18102 9.5065 6.03072 9.1426C5.88041 8.7787 5.80338 8.38872 5.80404 7.995C5.80338 7.60128 5.88041 7.2113 6.03072 6.8474C6.18102 6.4835 6.40165 6.15283 6.67996 5.87433C6.95827 5.59584 7.28879 5.37499 7.65259 5.22444C8.01639 5.0739 8.40632 4.99661 8.80004 4.997C9.19376 4.99661 9.58369 5.0739 9.94749 5.22444C10.3113 5.37499 10.6418 5.59584 10.9201 5.87433C11.1984 6.15283 11.4191 6.4835 11.5694 6.8474C11.7197 7.2113 11.7967 7.60128 11.796 7.995Z" fill="#050505"/>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M14.573 5.087L14.933 5.957C14.973 6.057 15.053 6.147 15.152 6.197C15.612 6.437 16.42 6.916 16.54 6.996C16.7 7.096 16.79 7.266 16.79 7.446V8.545C16.79 8.735 16.69 8.905 16.53 9.005C16.38 9.105 15.592 9.575 15.142 9.805C15.042 9.855 14.962 9.944 14.922 10.044L14.562 10.914C14.522 11.014 14.512 11.134 14.552 11.244C14.712 11.743 14.942 12.652 14.972 12.782C14.9926 12.8704 14.9906 12.9625 14.9661 13.0499C14.9416 13.1373 14.8955 13.2171 14.832 13.282L14.053 14.062C13.9883 14.1253 13.9086 14.1713 13.8214 14.1958C13.7342 14.2202 13.6423 14.2224 13.554 14.202C13.0367 14.0759 12.5238 13.9324 12.016 13.772C11.9112 13.7402 11.7987 13.7437 11.696 13.782L10.827 14.142C10.727 14.182 10.637 14.262 10.587 14.362C10.335 14.8326 10.0689 15.2954 9.78904 15.75C9.68904 15.91 9.51904 16 9.33904 16H8.24104C8.14852 15.9997 8.05762 15.9756 7.97707 15.9301C7.89652 15.8846 7.82902 15.8191 7.78104 15.74C7.68104 15.59 7.21204 14.801 6.98204 14.351C6.93102 14.2513 6.84584 14.1732 6.74204 14.131L5.87404 13.771C5.76891 13.7277 5.6516 13.7241 5.54404 13.761C5.04404 13.921 4.13604 14.151 4.00604 14.181C3.91765 14.2016 3.8255 14.1995 3.73811 14.175C3.65072 14.1506 3.57091 14.1045 3.50604 14.041L2.72804 13.262C2.66457 13.1971 2.61847 13.1173 2.594 13.0299C2.56953 12.9425 2.56748 12.8504 2.58804 12.762C2.62804 12.592 2.85804 11.692 3.01804 11.223C3.04987 11.1181 3.04636 11.0057 3.00804 10.903L2.64804 10.034C2.60587 9.9302 2.52779 9.84502 2.42804 9.794C1.96804 9.554 1.16004 9.074 1.04004 8.994C0.96337 8.94663 0.900127 8.88039 0.856358 8.8016C0.812589 8.72282 0.789756 8.63412 0.790042 8.544V7.447C0.790042 7.257 0.890042 7.087 1.05004 6.997C1.20004 6.897 1.98804 6.427 2.43804 6.197C2.53804 6.147 2.61804 6.057 2.65804 5.957L3.01804 5.088C3.05804 4.988 3.06804 4.868 3.02804 4.758C2.86804 4.258 2.63804 3.349 2.60804 3.219C2.58748 3.13061 2.58953 3.03846 2.614 2.95107C2.63847 2.86368 2.68457 2.78386 2.74804 2.719L3.52704 1.94C3.5918 1.87669 3.67145 1.83069 3.75865 1.80622C3.84585 1.78176 3.9378 1.77962 4.02604 1.8C4.19604 1.84 5.09504 2.07 5.56404 2.23C5.66892 2.26183 5.78135 2.25832 5.88404 2.22L6.75304 1.86C6.85304 1.82 6.94304 1.74 6.99304 1.64C7.23104 1.18 7.71004 0.37 7.79004 0.25C7.89004 0.09 8.06004 0 8.24004 0H9.33804C9.52804 0 9.69804 0.1 9.78804 0.26C9.88804 0.41 10.357 1.2 10.587 1.65C10.637 1.75 10.727 1.83 10.827 1.87L11.695 2.229C11.795 2.269 11.915 2.279 12.025 2.239C12.525 2.079 13.433 1.849 13.563 1.819C13.743 1.779 13.933 1.829 14.063 1.959L14.841 2.739C14.971 2.869 15.021 3.059 14.981 3.239C14.941 3.409 14.711 4.308 14.551 4.777C14.5192 4.88188 14.5227 4.99431 14.561 5.097L14.571 5.087H14.573ZM15.79 7.705V8.275C15.5 8.445 15.001 8.745 14.692 8.895C14.372 9.055 14.142 9.325 14.012 9.645L13.652 10.514C13.523 10.834 13.503 11.204 13.613 11.533C13.723 11.863 13.863 12.413 13.953 12.743L13.553 13.142C13.213 13.052 12.664 12.902 12.345 12.802C12.0144 12.6946 11.6562 12.7088 11.335 12.842L10.467 13.202C10.157 13.332 9.88704 13.572 9.72704 13.882C9.56804 14.192 9.27804 14.692 9.10904 14.981H8.52904C8.35904 14.691 8.05904 14.191 7.91004 13.881C7.75004 13.561 7.48004 13.321 7.17104 13.192L6.30204 12.832C5.98204 12.702 5.62304 12.682 5.28304 12.792C4.95304 12.902 4.40404 13.042 4.08504 13.132L3.68504 12.732C3.77504 12.392 3.92504 11.842 4.02504 11.523C4.12504 11.193 4.11504 10.833 3.98504 10.513L3.62504 9.644C3.49504 9.324 3.25504 9.064 2.94604 8.904C2.63604 8.744 2.14704 8.455 1.85804 8.285V7.715C2.14804 7.545 2.64704 7.245 2.95604 7.095C3.27604 6.935 3.50604 6.666 3.63604 6.346L3.99504 5.476C4.12504 5.157 4.14504 4.787 4.03504 4.457C3.92504 4.127 3.78504 3.578 3.69504 3.248L4.10504 2.838C4.44504 2.928 4.99404 3.068 5.31304 3.178C5.64304 3.288 6.00304 3.268 6.32304 3.138L7.19104 2.778C7.50104 2.648 7.77104 2.408 7.93104 2.098C8.09004 1.79 8.38004 1.29 8.55004 1H9.12004C9.29004 1.29 9.58904 1.79 9.73904 2.099C9.89904 2.419 10.169 2.659 10.478 2.789L11.347 3.149C11.667 3.279 12.026 3.299 12.365 3.189C12.695 3.079 13.245 2.939 13.574 2.849L13.984 3.259C13.894 3.598 13.754 4.148 13.644 4.467C13.544 4.797 13.554 5.157 13.684 5.477L14.044 6.347C14.174 6.667 14.413 6.926 14.723 7.086C15.033 7.246 15.532 7.536 15.811 7.706H15.791L15.79 7.705Z" fill="#050505"/>
                    </g>
                </svg>
            </button>

            <button class="size-9 bg-[#e8e8e8] rounded-[4px] flex items-center justify-center">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M13.376 1.69866C12.916 1.29066 13.564 0.586655 14.015 1.01366L16.2 3.05066C16.364 3.22066 16.364 3.44066 16.2 3.60466L14.042 5.93666C13.624 6.39666 12.935 5.74366 13.357 5.29866L14.713 3.82466C11.466 3.92366 8.84303 6.39165 8.84303 9.41265C8.84303 9.67066 8.63703 9.88266 8.37403 9.88266C8.31232 9.88279 8.25119 9.87071 8.19417 9.84713C8.13714 9.82354 8.08534 9.78891 8.04175 9.74523C7.99816 9.70155 7.96364 9.64967 7.94018 9.5926C7.91671 9.53552 7.90477 9.47437 7.90503 9.41265C7.90503 5.89365 10.908 3.00866 14.647 2.88666L13.375 1.69966L13.376 1.69866Z" fill="#050505"/>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M2.29004 3.43555C2.15743 3.43555 2.03025 3.48823 1.93649 3.58199C1.84272 3.67576 1.79004 3.80294 1.79004 3.93555V13.6205C1.79004 13.7532 1.84272 13.8803 1.93649 13.9741C2.03025 14.0679 2.15743 14.1205 2.29004 14.1205H15.29C15.4226 14.1205 15.5498 14.0679 15.6436 13.9741C15.7374 13.8803 15.79 13.7532 15.79 13.6205V7.66155C15.79 7.52894 15.8427 7.40176 15.9365 7.30799C16.0303 7.21423 16.1574 7.16155 16.29 7.16155C16.4226 7.16155 16.5498 7.21423 16.6436 7.30799C16.7374 7.40176 16.79 7.52894 16.79 7.66155V13.6215C16.79 14.0194 16.632 14.4009 16.3507 14.6822C16.0694 14.9635 15.6879 15.1215 15.29 15.1215H2.29004C1.89221 15.1215 1.51068 14.9635 1.22938 14.6822C0.948074 14.4009 0.790039 14.0194 0.790039 13.6215L0.790039 3.93555C0.790039 3.53772 0.948074 3.15619 1.22938 2.87489C1.51068 2.59358 1.89221 2.43555 2.29004 2.43555H6.77804C6.91065 2.43555 7.03782 2.48823 7.13159 2.58199C7.22536 2.67576 7.27804 2.80294 7.27804 2.93555C7.27804 3.06816 7.22536 3.19533 7.13159 3.2891C7.03782 3.38287 6.91065 3.43555 6.77804 3.43555H2.29004Z" fill="#050505"/>
                </svg>
            </button>

            <a href="{{ route('autofatture.nuova') }}" wire:navigate class="items-center gap-x-2 flex bg-black text-white px-4 py-2 text-sm rounded-[4px] transition">
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
                Crea autofattura
            </a>
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
        <input id="searchInput" wire:model.debounce.live.500ms="search" type="text" placeholder="Cerca per nome cliente o numero autofattura" class="bg-[#f5f5f5] rounded-[4px] border-0 ring-0 focus:ring-0 pr-4 py-2 w-full text-sm">
        <div id="shortcutHint" class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400 bg-white border border-gray-300 rounded px-1 py-0.5 pointer-events-none">
            ⌘K
        </div>
    </div>

    <div class="mb-3 text-sm text-gray-600 flex items-center gap-x-8">
        <button wire:click="$set('paymentStatusFilter', 'unpaid')" class="text-[#FC460E] hover:font-semibold focus:outline-none {{ $paymentStatusFilter === 'unpaid' ? 'font-semibold' : '' }}">
            Da incassare: {{ $unpaidCount }}
        </button>
    
        <button wire:click="$set('paymentStatusFilter', 'paid')" class="hover:font-semibold focus:outline-none {{ $paymentStatusFilter === 'paid' ? 'font-semibold' : '' }}">
            Incassate: {{ $paidCount }}
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
            Ci sono <strong>{{ $unpaidCount }} autofatture</strong> da incassare per un totale di <strong>€ {{ number_format($unpaidTotal, 2, ',', '.') }}</strong>
        </div>
    </div>

   

    <div class="">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="text-[#616161] text-xs border-b">
                    <th class="py-2 pl-2 pr-4 font-normal">Cliente</th>
                    <th class="py-2 px-4 font-normal">Numerazione</th>
                    <th class="py-2 px-4 font-normal">Stato</th>
                    <th class="py-2 px-4 font-normal">Importo</th>
                    <th class="py-2 px-4 font-normal">Netto post tasse</th>
                    <th class="py-2 px-4 font-normal">Data di emissione</th>
                    <th class="py-2 px-4 font-normal">Data di scadenza</th>
                    <th class="py-2 px-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($invoices as $invoice)
                    <tr class="hover:bg-[#f5f5f5] bg-white group transition-all duration-200">
                        <td class="whitespace-nowrap py-4 pl-2 pr-4">
                            <div class="flex items-center gap-2">
                                @if($invoice->client)
                                    <img src="{{ $invoice->client->logo }}" alt="{{ $invoice->client->name }}" class="w-8 h-8 rounded-full object-cover" />
                                @else
                                    <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                  <div class="font-normal text-gray-900 truncate">
                                    {{ $invoice->client->name ?? 'Cliente non trovato' }}
                                  </div>
                                  <div class="text-xs text-[#616161] lowercase truncate">
                                    {{ $invoice->client?->primaryContact?->email ?? '' }}
                                  </div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap py-4 px-4 font-normal text-gray-800">
                            <div class="flex items-center gap-2">
                                {{ $invoice->invoice_number }}
                            </div>
                        </td>
                        <td class="py-4 px-4 whitespace-nowrap">  
    
                            @if ($invoice->payment_status === 'Pagata')
                                <span class="bg-[#cff5d4] text-[#3aab53] border-[#3aab53] border text-xs font-medium px-3 py-1 rounded-full">
                            @elseif ($invoice->payment_status === 'Parziale')
                                <span class="bg-[#EFF8FF] text-[#395cd3] border-[#bfdbfe] border text-xs font-medium px-3 py-1 rounded-full">
                            @else
                                <span class="bg-[#fff4f0] text-[#FC460E] text-xs font-medium px-3 py-1 rounded-[4px]">
                            @endif
                                {{ $invoice->payment_status }}
                            </span>
                            
                        </td>
                        <td class="whitespace-nowrap py-4 px-4 text-gray-900 font-medium">{{ number_format($invoice->total, 2, ',', '.') }} EUR</td>
                        <td class="whitespace-nowrap py-4 px-4 text-gray-900 font-medium"><span class="font-normal">≈</span>{{ number_format($invoice->netto_post_tax, 2, ',', '.') }} EUR</td>
                        <td class="whitespace-nowrap py-4 px-4 text-gray-700">{{ strtolower($invoice->issue_date->locale('it')->isoFormat('DD MMM YYYY')) }}</td>
                        <td class="whitespace-nowrap py-4 px-4 text-gray-700">
                            {{ $invoice->deadline_date ? strtolower($invoice->deadline_date->locale('it')->isoFormat('DD MMM YYYY')) : '–' }}
                        </td>
                        <td class="whitespace-nowrap py-4 px-4 text-right">
                            <div class="flex justify-end items-center gap-x-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">                        
                                
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="0.392857" y="0.392857" width="19.2143" height="19.2143" rx="2.75" stroke="#AD96FF" stroke-width="0.785714"/>
                                    <path d="M5.74408 10H5.75063M10 10H10.0066M14.2494 10H14.256" stroke="#AD96FF" stroke-width="2.35714" stroke-linecap="round"/>
                                </svg>
                                    
                                
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $invoices->links() }}
        </div>
    </div>

</div> 