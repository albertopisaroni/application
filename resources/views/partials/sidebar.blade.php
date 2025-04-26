@php
    $path = request()->path();

    $selected = match (true) {
        str_starts_with($path, 'fatture') => 'fatture',
        str_starts_with($path, 'contatti') => 'contatti',
        str_starts_with($path, 'email') => 'email',
        str_starts_with($path, 'spese') => 'spese',
        str_starts_with($path, 'tasse') => 'tasse',
        str_starts_with($path, 'documenti') => 'documenti',
        default => 'dashboard',
    };

    $mainSidebar = $selected !== 'dashboard' ? 'true' : 'false';
    $secondarySidebar = $selected !== 'dashboard' ? 'true' : 'false';
@endphp

<div x-data="{ selected: '{{ $selected }}', mainSidebar: {{ $mainSidebar }}, secondarySidebar: {{ $secondarySidebar }}, animateBars: false  }" class="flex h-screen bg-white text-sm text-gray-800 font-normal lg:fixed lg:inset-y-0 lg:z-40" wire:ignore>
    <!-- Sidebar principale -->
    <div
        :class="mainSidebar ? '!w-[65px]' : '' "
        class="w-[250px] bg-white z-50 border-r border-[#E8E8E8] flex flex-col justify-between pb-4 pt-6 px-4 overflow-auto transition-all duration-200 ease-in"
    >
        <div>
            <!-- Header con logo e nome società -->
            <a href="{{ route('company.show') }}" wire:navigate class="flex items-center mb-6 space-x-2 my-1">
                <img class="w-8 h-8 rounded-full" alt="" src=" https://ticket.holdingshake.com/storage/project-photos/wl1rnS4AxsHuXutoULKPaMhqyVVxlaH4CecmpEon.png ">
                <span class="text-sm font-normal uppercase overflow-hidden text-ellipsis whitespace-nowrap mx-2 mr-2">HOLDINGS SHAKE DI PISARONI ALBERTO</span>
                <svg class="size-5 mt-[2px] mr-1" xmlns="http://www.w3.org/2000/svg" width="8" height="8" fill="currentColor" viewBox="0 0 8 8" class="cMc"><path d="M5.493 1.999a.234.234 0 01-.175-.075L3.998.604l-1.323 1.32c-.1.1-.255.1-.355 0-.1-.1-.1-.255 0-.355L3.819.075c.1-.1.255-.1.355 0l1.499 1.499c.1.1.1.255 0 .355a.251.251 0 01-.175.075l-.005-.005zm0 3.997a.234.234 0 00-.175.075l-1.32 1.32L2.68 6.07c-.1-.1-.255-.1-.355 0-.1.1-.1.255 0 .355l1.499 1.499c.1.1.255.1.355 0l1.499-1.499c.1-.1.1-.255 0-.355a.251.251 0 00-.175-.075h-.01z"></path></svg>
            </a>

            <!-- Navigazione -->
            <nav class="space-y-1 pt-3">

                <!-- Dashboard manuale -->
                <a href="{{ route('dashboard') }}" @click.prevent="selected = 'dashboard'; mainSidebar = false; secondarySidebar = false; setTimeout(() => Livewire.navigate('/'), 200); animateBars = true; setTimeout(() => animateBars = false, 500);"
                    class="hover:scale-1015 transform  flex items-center space-x-2 text-[#050505] py-1.5 px-2 rounded hover:border-[#f5f5f5] hover:bg-[#f5f5f5] text-[14px] mb-5 border transition duration-200 ease-in"
                    :class="selected === 'dashboard' ? 'text-[#050505] border-[#f5f5f5] bg-[#f5f5f5]' : 'text-[#050505] border-transparent'">
                    <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <mask id="mask0_1143_8442" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="16" height="16">
                        <path d="M0 0H16V16H0V0Z" fill="white"/>
                        </mask>
                        <g mask="url(#mask0_1143_8442)">
                        <path :class="selected === 'dashboard' ? 'fill-[#050505]' : 'fill-[#050505]'" fill-rule="evenodd" clip-rule="evenodd" d="M14.91 14.7353V7.18533L8 1.26633L1.09 7.18633V14.7363H14.91V14.7353ZM16 7.18533V14.7353C16 15.3353 15.51 15.8253 14.91 15.8253H1.09C0.49 15.8253 0 15.3353 0 14.7353V7.18533C0 6.86533 0.14 6.56533 0.38 6.35533L7.29 0.436328C7.7 0.0863281 8.3 0.0863281 8.71 0.436328L15.62 6.35633C15.86 6.56633 16 6.86533 16 7.18533Z"/>
                        </g>
                    </svg>
                    <span class="truncate block min-w-0">Dashboard</span>
                </a>


                <div class="space-y-2">
                    <template x-for="item in [
                        { name: 'Fatture', value: 'fatture', icon: 'invoice' },
                        { name: 'Contatti', value: 'contatti', icon: 'contatti' },
                        { name: 'Spese', value: 'spese', icon: 'spese' },
                        { name: 'Tasse e Tributi', value: 'tasse', icon: 'tasse' },
                        { name: 'Documenti', value: 'documenti', icon: 'documenti' },
                        { name: 'email', value: 'email', icon: 'mail' }
                    ]" :key="item.value">
                    <a href="#" @click.prevent="selected = item.value; mainSidebar = true; secondarySidebar = true; animateBars = true; setTimeout(() => animateBars = false, 500);" 
                        class="hover:scale-1015 transform  flex  items-center py-1.5 text-[#050505] px-2 rounded hover:border-[#f5f5f5] hover:bg-[#f5f5f5] text-[14px] border transition duration-200 ease-in"
                        :class="selected === item.value ? 'text-[#050505] border-[#f5f5f5] bg-[#f5f5f5]' : 'text-[#050505] border-transparent'">

                            <template x-if="item.icon === 'invoice'">
                                <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <mask id="mask0_564_5456" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="16" height="16">
                                    <path d="M0 0H16V16H0V0Z" fill="white"/>
                                    </mask>
                                    <g mask="url(#mask0_564_5456)">
                                    <path :class="selected === item.value ? 'fill-[#050505]' : 'fill-[#050505]'" fill-rule="evenodd" clip-rule="evenodd" d="M6.73999 9C6.73999 8.72 6.95999 8.5 7.23999 8.5H12.24C12.52 8.5 12.74 8.72 12.74 9C12.74 9.28 12.52 9.5 12.24 9.5H7.23999C6.95999 9.5 6.73999 9.28 6.73999 9ZM6.73999 12C6.73999 11.72 6.95999 11.5 7.23999 11.5H10.24C10.52 11.5 10.74 11.72 10.74 12C10.74 12.28 10.52 12.5 10.24 12.5H7.23999C6.95999 12.5 6.73999 12.28 6.73999 12Z"/>
                                    <path :class="selected === item.value ? 'fill-[#050505]' : 'fill-[#050505]'" fill-rule="evenodd" clip-rule="evenodd" d="M12.95 5.06L14.06 4H5.23998V15H14.24V6.6C14.24 6.6 14.32 6.52 14.37 6.48L15.24 5.61V15C15.24 15.55 14.79 16 14.24 16H5.23998C4.67999 16 4.23998 15.55 4.23998 15V12.96H1.93999L3.04998 14.06C3.23998 14.26 3.23998 14.58 3.04998 14.77C2.84998 14.97 2.53998 14.97 2.33998 14.77L0.409985 12.84C0.355272 12.7932 0.311639 12.7348 0.282239 12.6691C0.252838 12.6034 0.238406 12.532 0.239985 12.46C0.239985 12.33 0.279985 12.2 0.379985 12.11L2.33998 10.15C2.53998 9.95 2.84998 9.95 3.04998 10.15C3.24998 10.35 3.23998 10.66 3.04998 10.85L1.93999 11.96H4.23998V4C4.23998 3.45 4.67999 3 5.23998 3H14.06L12.95 1.85C12.8586 1.75641 12.8075 1.6308 12.8075 1.5C12.8075 1.3692 12.8586 1.24359 12.95 1.15C13.15 0.95 13.46 0.95 13.66 1.15L15.59 3.08C15.7 3.17 15.76 3.31 15.76 3.46C15.76 3.59 15.72 3.71 15.62 3.81L13.66 5.77C13.46 5.97 13.15 5.97 12.95 5.77C12.8584 5.67449 12.8073 5.5473 12.8073 5.415C12.8073 5.2827 12.8584 5.15551 12.95 5.06Z"/>
                                    <path :class="selected === item.value ? 'fill-[#050505]' : 'fill-[#050505]'" d="M3.23999 1V9.1C2.91999 8.98 2.56999 8.97 2.23999 9.07V1C2.23999 0.45 2.68999 0 3.23999 0H12.24C12.41 0 12.58 0.04 12.72 0.12C12.5303 0.202622 12.3596 0.323379 12.2185 0.474766C12.0775 0.626153 11.969 0.804934 11.9 1H3.23999Z"/>
                                    </g>
                                </svg>
                            </template>
                            <template x-if="item.icon === 'abbonamenti'">
                                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3" />
                                </svg>
                                  
                            </template>
                            <template x-if="item.icon === 'contatti'">
                                <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <mask id="mask0_564_5456" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="16" height="16">
                                    <path d="M0 0H16V16H0V0Z" fill="white"/>
                                    </mask>
                                    <g mask="url(#mask0_564_5456)">
                                    <path :class="selected === item.value ? 'fill-[#050505]' : 'fill-[#050505]'" fill-rule="evenodd" clip-rule="evenodd" d="M6.73999 9C6.73999 8.72 6.95999 8.5 7.23999 8.5H12.24C12.52 8.5 12.74 8.72 12.74 9C12.74 9.28 12.52 9.5 12.24 9.5H7.23999C6.95999 9.5 6.73999 9.28 6.73999 9ZM6.73999 12C6.73999 11.72 6.95999 11.5 7.23999 11.5H10.24C10.52 11.5 10.74 11.72 10.74 12C10.74 12.28 10.52 12.5 10.24 12.5H7.23999C6.95999 12.5 6.73999 12.28 6.73999 12Z"/>
                                    <path :class="selected === item.value ? 'fill-[#050505]' : 'fill-[#050505]'" fill-rule="evenodd" clip-rule="evenodd" d="M12.95 5.06L14.06 4H5.23998V15H14.24V6.6C14.24 6.6 14.32 6.52 14.37 6.48L15.24 5.61V15C15.24 15.55 14.79 16 14.24 16H5.23998C4.67999 16 4.23998 15.55 4.23998 15V12.96H1.93999L3.04998 14.06C3.23998 14.26 3.23998 14.58 3.04998 14.77C2.84998 14.97 2.53998 14.97 2.33998 14.77L0.409985 12.84C0.355272 12.7932 0.311639 12.7348 0.282239 12.6691C0.252838 12.6034 0.238406 12.532 0.239985 12.46C0.239985 12.33 0.279985 12.2 0.379985 12.11L2.33998 10.15C2.53998 9.95 2.84998 9.95 3.04998 10.15C3.24998 10.35 3.23998 10.66 3.04998 10.85L1.93999 11.96H4.23998V4C4.23998 3.45 4.67999 3 5.23998 3H14.06L12.95 1.85C12.8586 1.75641 12.8075 1.6308 12.8075 1.5C12.8075 1.3692 12.8586 1.24359 12.95 1.15C13.15 0.95 13.46 0.95 13.66 1.15L15.59 3.08C15.7 3.17 15.76 3.31 15.76 3.46C15.76 3.59 15.72 3.71 15.62 3.81L13.66 5.77C13.46 5.97 13.15 5.97 12.95 5.77C12.8584 5.67449 12.8073 5.5473 12.8073 5.415C12.8073 5.2827 12.8584 5.15551 12.95 5.06Z"/>
                                    <path :class="selected === item.value ? 'fill-[#050505]' : 'fill-[#050505]'" d="M3.23999 1V9.1C2.91999 8.98 2.56999 8.97 2.23999 9.07V1C2.23999 0.45 2.68999 0 3.23999 0H12.24C12.41 0 12.58 0.04 12.72 0.12C12.5303 0.202622 12.3596 0.323379 12.2185 0.474766C12.0775 0.626153 11.969 0.804934 11.9 1H3.23999Z"/>
                                    </g>
                                </svg>
                            </template>
                            <template x-if="item.icon === 'spese'">
                                <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <mask id="mask0_561_10353" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="16" height="16">
                                    <path d="M0 0H16V16H0V0Z" fill="white"/>
                                    </mask>
                                    <g mask="url(#mask0_561_10353)">
                                    <path :class="selected === item.value ? 'fill-[#050505]' : 'fill-[#050505]'" d="M7.47998 7.49805C7.47998 7.21805 7.70998 6.99805 7.98998 6.99805H12.05C12.33 6.99805 12.56 7.21805 12.56 7.49805C12.56 7.77805 12.33 7.99805 12.05 7.99805H7.98998C7.70998 7.99805 7.47998 7.77805 7.47998 7.49805ZM7.99998 8.99805C7.71998 8.99805 7.48998 9.21805 7.48998 9.49805C7.48998 9.77805 7.71998 9.99805 7.99998 9.99805H12.06C12.34 9.99805 12.57 9.77805 12.57 9.49805C12.57 9.21805 12.34 8.99805 12.06 8.99805H7.99998Z"/>
                                    <path :class="selected === item.value ? 'fill-[#050505]' : 'fill-[#050505]'" fill-rule="evenodd" clip-rule="evenodd" d="M4.95001 13.7281V15.6281C4.95001 15.8581 5.19001 15.9981 5.39001 15.8981L6.64001 15.2881L7.89001 15.8981C8.17001 16.0381 8.50001 16.0381 8.78001 15.8981L10.03 15.2881L11.28 15.8981C11.56 16.0381 11.89 16.0381 12.17 15.8981L13.42 15.2881L14.67 15.8981C14.87 15.9981 15.11 15.8481 15.11 15.6281V3.3981C15.11 3.1681 14.87 3.02811 14.67 3.1281L13.42 3.7381L12.17 3.1281C12.0318 3.05902 11.8795 3.02305 11.725 3.02305C11.5705 3.02305 11.4182 3.05902 11.28 3.1281L11.05 3.2381V0.368105C11.05 0.138105 10.81 -0.011895 10.6 0.098105L9.35001 0.748105L8.13001 0.118105C7.98588 0.039331 7.82427 -0.00195313 7.66001 -0.00195312C7.49576 -0.00195313 7.33414 0.039331 7.19001 0.118105L5.97001 0.758105L4.75001 0.118105C4.60588 0.039331 4.44427 -0.00195312 4.28001 -0.00195312C4.11576 -0.00195312 3.95414 0.039331 3.81001 0.118105L2.59001 0.758105L1.34001 0.098105C1.13001 -0.00189499 0.890015 0.138105 0.890015 0.368105V13.5781C0.890015 13.8081 1.13001 13.9581 1.34001 13.8481L2.59001 13.1981L3.81001 13.8381C4.11001 13.9981 4.46001 13.9981 4.75001 13.8381L4.95001 13.7281ZM2.58001 1.89811L4.27001 1.00811L5.96001 1.89811L7.65001 1.00811L9.34001 1.89811L10.02 1.53811V3.7281L8.77002 3.1181C8.63185 3.04902 8.47949 3.01305 8.32501 3.01305C8.17054 3.01305 8.01818 3.04902 7.88001 3.1181L6.63001 3.7281L5.38001 3.1181C5.18002 3.01811 4.94001 3.1681 4.94001 3.3881V12.5681L4.26002 12.9281L2.57001 12.0381L1.89001 12.3981V1.54811L2.57001 1.90811L2.58001 1.89811ZM8.34001 4.03811L6.65001 4.8581L5.97001 4.5281V14.4781L6.65001 14.1481L8.34001 14.9681L10.03 14.1481L11.72 14.9681L13.41 14.1481L14.09 14.4781V4.53811L13.41 4.8681L11.72 4.0481L10.03 4.8681L8.34001 4.0481V4.03811Z"/>
                                    </g>
                                </svg>
                            </template>
                            <template x-if="item.icon === 'tasse'">
                                <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <mask id="mask0_561_10372" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="16" height="16">
                                    <path d="M0 0H16V16H0V0Z" fill="white"/>
                                    </mask>
                                    <g mask="url(#mask0_561_10372)">
                                    <path :class="selected === item.value ? 'fill-[#050505]' : 'fill-[#050505]'" fill-rule="evenodd" clip-rule="evenodd" d="M2 1H14V15H2V1ZM14 0H2C1.45 0 1 0.45 1 1V15C1 15.55 1.45 16 2 16H14C14.55 16 15 15.55 15 15V1C15 0.45 14.55 0 14 0ZM4 3H12V5H4V3ZM12 2H4C3.45 2 3 2.45 3 3V5C3 5.55 3.45 6 4 6H12C12.55 6 13 5.55 13 5V3C13 2.45 12.55 2 12 2ZM5 13C5 13.55 4.55 14 4 14C3.45 14 3 13.55 3 13C3 12.45 3.45 12 4 12C4.55 12 5 12.45 5 13ZM12 14C11.45 14 11 13.55 11 13C11 12.45 11.45 12 12 12C12.55 12 13 12.45 13 13C13 13.55 12.55 14 12 14ZM7 13C7 13.55 7.45 14 8 14C8.55 14 9 13.55 9 13C9 12.45 8.55 12 8 12C7.45 12 7 12.45 7 13ZM4 10C4.55 10 5 9.55 5 9C5 8.45 4.55 8 4 8C3.45 8 3 8.45 3 9C3 9.55 3.45 10 4 10ZM11 9C11 9.55 11.45 10 12 10C12.55 10 13 9.55 13 9C13 8.45 12.55 8 12 8C11.45 8 11 8.45 11 9ZM8 10C7.45 10 7 9.55 7 9C7 8.45 7.45 8 8 8C8.55 8 9 8.45 9 9C9 9.55 8.55 10 8 10Z"/>
                                    </g>
                                </svg>
                            </template>
                            <template x-if="item.icon === 'documenti'">
                                <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <mask id="mask0_561_10333" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="16" height="16">
                                    <path d="M0 0H16V16H0V0Z" fill="white"/>
                                    </mask>
                                    <g mask="url(#mask0_561_10333)">
                                    <path :class="selected === item.value ? 'fill-[#050505]' : 'fill-[#050505]'" fill-rule="evenodd" clip-rule="evenodd" d="M15 6.4025C15.55 6.4025 16 5.9225 16 5.3325V4.8525C16 4.4525 15.79 4.0925 15.46 3.9125L8.78 0.2025C8.29 -0.0675 7.71 -0.0675 7.21 0.2025L0.54 3.9125C0.21 4.0925 0 4.4525 0 4.8525V5.3325C0 5.9225 0.45 6.4025 1 6.4025H2V12.8025H1C0.45 12.8025 0 13.2825 0 13.8725V14.9325C0 15.5225 0.45 16.0025 1 16.0025H15C15.55 16.0025 16 15.5225 16 14.9325V13.8725C16 13.2825 15.55 12.8025 15 12.8025H14V6.4025H15ZM15 13.8725V14.9325H1V13.8725H15ZM3 12.8025V6.4025H5V12.8025H3ZM6 12.8025V6.4025H10V12.8025H6ZM11 12.8025V6.4025H13V12.8025H11ZM13.02 5.3325H1V4.8525L7.68 1.1525C7.88 1.0425 8.12 1.0425 8.32 1.1525L15 4.8525V5.3325H13.02Z"/>
                                    </g>
                                </svg>
                            </template>
                            <template x-if="item.icon === 'mail'">
                                <svg class="flex-shrink-0" width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path :class="selected === item.value ? 'stroke-[#050505]' : 'stroke-[#050505]'" d="M12.5667 13.1668H3.43337C2.55337 13.1668 1.83337 12.4535 1.83337 11.5668V5.4335C1.83337 4.5535 2.54671 3.8335 3.43337 3.8335H12.5667C13.4467 3.8335 14.1667 4.54683 14.1667 5.4335V11.5668C14.1667 12.4535 13.4534 13.1668 12.5667 13.1668Z" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path :class="selected === item.value ? 'stroke-[#050505]' : 'stroke-[#050505]'" d="M13.48 4.34668L8.89997 8.89335C8.38664 9.40668 7.61331 9.40668 7.09331 8.89335L2.51331 4.34668" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path :class="selected === item.value ? 'stroke-[#050505]' : 'stroke-[#050505]'" d="M9.42004 8.44678L13.48 12.3868" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path :class="selected === item.value ? 'stroke-[#050505]' : 'stroke-[#050505]'" d="M2.52002 12.3867L6.62669 8.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </template>

                            <span class="ml-2 truncate block min-w-0" x-text="item.name"></span>
                        </a>
                    </template>
                </div>
            </nav>
        </div>

        <!-- Footer -->
        <div class="space-y-3 text-gray-500 mt-16 font-normal text-[14px] px-2 pb-2">
            
            <div 
                x-show="!mainSidebar"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-4"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="space-y-3"
            >
                <a href="#" class="block hover:text-gray-900 truncate">Impostazioni</a>
                <a href="#" class="block hover:text-gray-900 truncate">Gestione utenti</a>
                <a href="#" class="block hover:text-gray-900 truncate">Integrazioni e partnership</a>
                <a href="#" class="block hover:text-gray-900 truncate">Consiglia Newo</a>
            </div>

            <a href="#" x-show="mainSidebar">
                <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path :class="selected === 'settings' ? 'stroke-[#050505]' : 'stroke-[#050505]'" d="M8 10C9.10457 10 10 9.10457 10 8C10 6.89543 9.10457 6 8 6C6.89543 6 6 6.89543 6 8C6 9.10457 6.89543 10 8 10Z"/>
                    <path :class="selected === 'settings' ? 'stroke-[#050505]' : 'stroke-[#050505]'" d="M9.1766 1.43483C8.93193 1.3335 8.62127 1.3335 7.99993 1.3335C7.3786 1.3335 7.06793 1.3335 6.82327 1.43483C6.66139 1.50184 6.5143 1.60009 6.39042 1.72398C6.26653 1.84786 6.16828 1.99495 6.10127 2.15683C6.03993 2.3055 6.01527 2.4795 6.00593 2.73216C6.00159 2.91478 5.951 3.09332 5.8589 3.25107C5.76679 3.40881 5.63617 3.54062 5.47927 3.63416C5.31981 3.72334 5.14032 3.77061 4.95762 3.77154C4.77492 3.77248 4.59497 3.72704 4.4346 3.6395C4.2106 3.52083 4.0486 3.4555 3.88793 3.43416C3.53748 3.38808 3.18307 3.48303 2.9026 3.69816C2.69327 3.86016 2.53727 4.12883 2.2266 4.66683C1.91593 5.20483 1.75993 5.4735 1.72593 5.73683C1.70302 5.91046 1.71455 6.08691 1.75985 6.25609C1.80514 6.42527 1.88333 6.58387 1.98993 6.72283C2.0886 6.85083 2.2266 6.95816 2.4406 7.09283C2.75593 7.29083 2.9586 7.62816 2.9586 8.00016C2.9586 8.37216 2.75593 8.7095 2.4406 8.90683C2.2266 9.04216 2.08793 9.1495 1.98993 9.2775C1.88333 9.41645 1.80514 9.57505 1.75985 9.74423C1.71455 9.91341 1.70302 10.0899 1.72593 10.2635C1.7606 10.5262 1.91593 10.7955 2.22593 11.3335C2.53727 11.8715 2.6926 12.1402 2.9026 12.3022C3.04156 12.4088 3.20016 12.487 3.36934 12.5323C3.53852 12.5775 3.71497 12.5891 3.8886 12.5662C4.0486 12.5448 4.2106 12.4795 4.4346 12.3608C4.59497 12.2733 4.77492 12.2278 4.95762 12.2288C5.14032 12.2297 5.31981 12.277 5.47927 12.3662C5.80127 12.5528 5.9926 12.8962 6.00593 13.2682C6.01527 13.5215 6.03927 13.6948 6.10127 13.8435C6.16828 14.0054 6.26653 14.1525 6.39042 14.2763C6.5143 14.4002 6.66139 14.4985 6.82327 14.5655C7.06793 14.6668 7.3786 14.6668 7.99993 14.6668C8.62127 14.6668 8.93193 14.6668 9.1766 14.5655C9.33848 14.4985 9.48557 14.4002 9.60945 14.2763C9.73334 14.1525 9.83159 14.0054 9.8986 13.8435C9.95993 13.6948 9.9846 13.5215 9.99393 13.2682C10.0073 12.8962 10.1986 12.5522 10.5206 12.3662C10.6801 12.277 10.8595 12.2297 11.0422 12.2288C11.2249 12.2278 11.4049 12.2733 11.5653 12.3608C11.7893 12.4795 11.9513 12.5448 12.1113 12.5662C12.2849 12.5891 12.4613 12.5775 12.6305 12.5323C12.7997 12.487 12.9583 12.4088 13.0973 12.3022C13.3073 12.1408 13.4626 11.8715 13.7733 11.3335C14.0839 10.7955 14.2399 10.5268 14.2739 10.2635C14.2968 10.0899 14.2853 9.91341 14.24 9.74423C14.1947 9.57505 14.1165 9.41645 14.0099 9.2775C13.9113 9.1495 13.7733 9.04216 13.5593 8.9075C13.4032 8.81243 13.2738 8.67932 13.1832 8.52064C13.0927 8.36195 13.0438 8.18287 13.0413 8.00016C13.0413 7.62816 13.2439 7.29083 13.5593 7.0935C13.7733 6.95816 13.9119 6.85083 14.0099 6.72283C14.1165 6.58387 14.1947 6.42527 14.24 6.25609C14.2853 6.08691 14.2968 5.91046 14.2739 5.73683C14.2393 5.47416 14.0839 5.20483 13.7739 4.66683C13.4626 4.12883 13.3073 3.86016 13.0973 3.69816C12.9583 3.59156 12.7997 3.51337 12.6305 3.46807C12.4613 3.42278 12.2849 3.41125 12.1113 3.43416C11.9513 3.4555 11.7893 3.52083 11.5646 3.6395C11.4043 3.72692 11.2245 3.77229 11.0419 3.77136C10.8593 3.77043 10.68 3.72322 10.5206 3.63416C10.3637 3.54062 10.2331 3.40881 10.141 3.25107C10.0489 3.09332 9.99827 2.91478 9.99393 2.73216C9.9846 2.47883 9.9606 2.3055 9.8986 2.15683C9.83159 1.99495 9.73334 1.84786 9.60945 1.72398C9.48557 1.60009 9.33848 1.50184 9.1766 1.43483Z"/>
                </svg>
            </a>

            <div class="flex justify-between items-center !mt-8">

                <div id="newo-logo" class="flex items-center mb-2">

                    <svg class="origin-center -rotate-[12deg] -ml-[3px]" x-show="mainSidebar" width="22" height="21" viewBox="0 0 25 21" fill="none" xmlns="http://www.w3.org/2000/svg" x-show="true" class="w-auto h-[21px]">
                        <g clip-path="url(#clip0_924_6609)">
                        <path :class="animateBars ? '-rotate-[35deg]' : 'translate-y-0'" class="transition-transform duration-200 origin-center" d="M6.34965 0.562185C7.47543 -0.170489 8.98092 0.147338 9.71359 1.27311L19.2685 16.2528C20.0011 17.3786 19.6833 18.884 18.5575 19.6167C17.4318 20.3494 15.9263 20.0316 15.1936 18.9058L5.63872 3.92613C4.90605 2.80035 5.22388 1.29486 6.34965 0.562185Z" fill="url(#paint0_linear_924_6609)" stroke="white" stroke-width="0.334554" stroke-miterlimit="10"/>
                        <path class="transition-transform duration-200 origin-center" d="M22.2247 14.3492C23.3193 14.5807 24.3941 13.8814 24.6255 12.7872C24.8569 11.6931 24.1571 10.6185 23.0625 10.3871L3.09953 6.16563C2.00496 5.93417 0.930071 6.6335 0.698706 7.72762C0.46734 8.82174 1.16711 9.89633 2.26168 10.1278L22.2247 14.3492Z" fill="url(#paint1_linear_924_6609)" stroke="white" stroke-width="0.306117" stroke-miterlimit="10"/>
                        <path :class="animateBars ? 'rotate-[33deg]' : 'translate-y-0'" class="transition-transform duration-200 origin-center" d="M3.88866 13.4182C2.89991 14.0618 2.62009 15.385 3.26366 16.3738C3.90723 17.3625 5.23049 17.6423 6.21924 16.9988L21.7262 6.90533C22.715 6.26175 22.9948 4.93849 22.3512 3.94974C21.7076 2.961 20.3844 2.68118 19.3956 3.32475L3.88866 13.4182Z" fill="url(#paint2_linear_924_6609)" stroke="white" stroke-width="0.312808" stroke-miterlimit="10"/>
                        </g>
                        <defs>
                        <linearGradient id="paint0_linear_924_6609" x1="3.98804" y1="8.29843" x2="20.925" y2="11.88" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#39006B"/>
                        <stop offset="0.06" stop-color="#460488"/>
                        <stop offset="0.17" stop-color="#580BB0"/>
                        <stop offset="0.27" stop-color="#6610CF"/>
                        <stop offset="0.36" stop-color="#7014E5"/>
                        <stop offset="0.45" stop-color="#7616F3"/>
                        <stop offset="0.54" stop-color="#7917F8"/>
                        <stop offset="1" stop-color="#AD96FF"/>
                        </linearGradient>
                        <linearGradient id="paint1_linear_924_6609" x1="0.654084" y1="7.93055" x2="24.5825" y2="12.9905" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#F83774"/>
                        <stop offset="0.12" stop-color="#F93D4C"/>
                        <stop offset="0.23" stop-color="#FA412D"/>
                        <stop offset="0.34" stop-color="#FB4417"/>
                        <stop offset="0.44" stop-color="#FB4609"/>
                        <stop offset="0.54" stop-color="#FC4705"/>
                        <stop offset="1" stop-color="#FFDB2F"/>
                        </linearGradient>
                        <linearGradient id="paint2_linear_924_6609" x1="0.383224" y1="8.13248" x2="16.8117" y2="10.5116" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#39006B"/>
                        <stop offset="0.06" stop-color="#460488"/>
                        <stop offset="0.17" stop-color="#580BB0"/>
                        <stop offset="0.27" stop-color="#6610CF"/>
                        <stop offset="0.36" stop-color="#7014E5"/>
                        <stop offset="0.45" stop-color="#7616F3"/>
                        <stop offset="0.54" stop-color="#7917F8"/>
                        <stop offset="1" stop-color="#AD96FF"/>
                        </linearGradient>
                        <clipPath id="clip0_924_6609">
                        <rect width="24.3221" height="20.177" fill="white" transform="translate(0.5)"/>
                        </clipPath>
                        </defs>
                    </svg>

                    <svg width="63" height="15" viewBox="0 0 59 13" fill="none" xmlns="http://www.w3.org/2000/svg" x-show="!mainSidebar" x-transition x-cloak>
                        <path class="fill-black" d="M3.23685 0.386022H6.90871L6.47226 2.47444C6.33521 3.14029 5.78406 3.96702 5.50848 4.44816C5.41613 4.65521 5.36995 4.81609 5.57701 4.90696C5.80641 4.99931 5.98963 4.76991 6.01346 4.6999C7.25281 2.42677 9.47976 0.0166016 12.0046 0.0166016C14.7589 0.0166016 14.6904 1.8071 13.9098 5.52514L12.4172 12.5039H8.7439L10.0533 6.35187C10.5806 3.89552 10.6729 3.04645 9.70916 3.04645C8.40129 3.04645 5.32526 7.89062 4.63558 11.0575L4.33766 12.5039H0.664307L3.23536 0.384533L3.23685 0.386022Z"/>
                        <path class="fill-black" d="M22.8176 0.019556C25.481 0.019556 27.6156 1.51213 27.27 3.55438C26.7427 6.46952 22.9085 7.64035 18.1343 7.64035C18.1567 8.6503 18.5484 9.93582 20.613 9.93582C21.83 9.93582 23.0008 9.17761 23.3911 8.28236H26.9721C26.0083 10.8296 23.2764 12.8733 20.2004 12.8733C15.7018 12.8733 14.163 9.95816 14.8065 6.44569C15.4486 2.77234 18.5484 0.0180664 22.8176 0.0180664V0.019556ZM23.552 3.75994C23.6443 3.16261 23.0708 2.68147 22.1979 2.68147C20.0633 2.68147 18.9149 4.17405 18.4799 5.43574C21.6021 5.43574 23.3226 5.09164 23.552 3.75994Z"/>
                        <path class="fill-black" d="M28.4186 4.58646L29.3138 0.385791H32.9872L32.1843 4.15001C31.3352 8.21364 31.289 9.65855 32.2305 9.65855C33.3789 9.65855 34.87 7.11133 36.2941 0.384302H39.9674L38.7504 5.89284C38.1769 8.51007 37.9475 9.65706 38.9113 9.65706C40.2654 9.65706 42.0097 6.32929 43.2729 0.382812H46.9447C45.7739 6.64956 42.2167 12.6869 38.2678 12.6869C36.6144 12.6869 35.8338 11.379 36.2479 9.01355C36.2703 8.87651 36.2703 8.64562 35.9723 8.64562C35.6744 8.64562 35.5359 8.9212 35.4897 9.01355C34.2965 11.6308 32.6654 12.6869 31.0373 12.6869C28.7642 12.6869 27.1584 10.5985 28.4201 4.58348L28.4186 4.58646Z"/>
                        <path class="fill-black" d="M58.8793 6.44569C58.2373 9.98051 55.2983 12.8733 51.3047 12.8733C47.3111 12.8733 45.382 9.98051 46.0255 6.44569C46.6676 2.91087 49.6065 0.0180664 53.6002 0.0180664C57.5938 0.0180664 59.5228 2.91087 58.8793 6.44569ZM55.206 6.44569C55.5277 4.65519 54.8619 3.14027 53.049 3.14027C51.2362 3.14027 50.0192 4.65519 49.6974 6.44569C49.3757 8.23619 50.0415 9.75111 51.8558 9.75111C53.6702 9.75111 54.8857 8.23619 55.2074 6.44569H55.206Z"/>
                    </svg>

                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar secondaria -->
        <div
            :class="secondarySidebar ? '!left-[65px]' : '' "
            class="w-[250px] h-full absolute left-0 transition-all duration-200 overflow-hidden border-r border-[#E8E8E8] px-4 pt-8 pb-6 "
        >

<div x-show="selected === 'dashboard'" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    class="absolute w-[calc(100%-2rem)]">
            
        </div>

        <!-- Fatture -->
        <div x-show="selected === 'fatture'"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    class="absolute w-[calc(100%-2rem)]">
            <div>
                <h2 class="text-[16px] font-normal text-[#050505] mb-2 mt-0.5">Fatture</h2>
                <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Ordinaria</h3>
                <nav class="space-y-1 text-sm">
                    <a href="{{ route('fatture.list') }}" wire:navigate class="block rounded py-1.5 px-2 bg-gray-100 text-gray-900">Fatture</a>
                    <a href="#" class="block rounded py-1.5 px-2 hover:bg-gray-100">Lavorazioni</a>
                    <a href="#" class="block rounded py-1.5 px-2 hover:bg-gray-100">Preventivi</a>
                </nav>
                <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Ricorrenti</h3>
                <nav class="space-y-1 text-sm">
                    <a href="#" class="block rounded py-1.5 px-2 hover:bg-gray-100">Fatture ricorrenti</a>
                    <a wire:navigate href="{{ route('abbonamenti.lista') }}" class="block rounded py-1.5 px-2 hover:bg-gray-100">Abbonamenti</a>
                    <a href="#" class="block rounded py-1.5 px-2 hover:bg-gray-100">Transazioni</a>
                </nav>
                <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Gestione</h3>
                <nav class="space-y-1 text-sm">
                    <a href="#" class="block rounded py-1.5 px-2 hover:bg-gray-100">Prodotti</a>
                    <a href="#" class="block rounded py-1.5 px-2 hover:bg-gray-100">Numerazioni</a>
                </nav>
            </div>
        </div>

        <!-- Contatti -->
        <div x-show="selected === 'contatti'" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transitidivon:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    class="absolute w-[calc(100%-2rem)]">
            <div>
                <h2 class="text-[16px] font-normal text-[#050505] mb-2">Contatti</h2>
                <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Anagrafiche</h3>
                <nav class="space-y-1 text-sm">
                    <a href="{{ route('contatti.clienti.lista') }}" wire:navigate class="block rounded py-1.5 px-2 hover:bg-gray-100">Clienti</a>
                    <a href="#" class="block rounded py-1.5 px-2 hover:bg-gray-100 line-through">Fornitori</a>
                    <a href="#" class="block rounded py-1.5 px-2 hover:bg-gray-100 line-through">Collaboratori</a>
                </nav>
            </div>
        </div>

        <div x-show="selected === 'email'" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    class="absolute w-[calc(100%-2rem)]">
            <div>
                <h2 class="text-[16px] font-normal text-[#050505] mb-2">Email</h2>
                <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Anagrafiche</h3>
                <nav class="space-y-1 text-sm">
                    <a href="{{ route('email.list') }}" wire:navigate class="block rounded py-1.5 px-2 hover:bg-gray-100">Email</a>
                </nav>
            </div>
        </div>
    

        <!-- Dashboard o fallback -->
        <div x-show="!['fatture', 'contatti', 'email', 'dashboard'].includes(selected)" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    class="absolute w-[calc(100%-2rem)]">
            <div>
                <h2 class="text-[16px] font-normal text-[#050505] mb-2 capitalize" x-text="selected"></h2>
                <p class="text-xs text-gray-500">Nessun sottomenu disponibile</p>
            </div>
        </div>
    </div>
</div>
