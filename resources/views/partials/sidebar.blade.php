<div>


    
    <!-- Mobile Menu Toggle Button -->
    <button
        @click="mobileMenuOpen = true; mobileSubmenuOpen = false"
        class="lg:hidden fixed top-4 left-4 z-40 p-2 rounded-md text-gray-700 bg-white shadow-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-purple-500"
        aria-label="Open menu"
    >
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
    
    <!-- Mobile Menu Backdrop -->
    <div
        x-show="mobileMenuOpen"
        x-cloak
        @click="mobileMenuOpen = false; mobileSubmenuOpen = false"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="lg:hidden fixed inset-0 bg-gray-600 bg-opacity-75 z-30"
        aria-hidden="true"
    ></div>
    
    <!-- Sidebar -->
    <div
    class="flex h-screen bg-white text-sm text-gray-800 font-normal fixed inset-y-0 z-50 transition-transform duration-300 ease-in-out lg:translate-x-0" 
    :class="{ '-translate-x-full': !mobileMenuOpen && window.innerWidth < 1024 }"
    wire:ignore>

    <!-- Sidebar principale -->
    <div
        :class="mainSidebar ? 'lg:!w-[65px]' : '' "
        class="w-[250px] bg-white z-50 border-r border-[#E8E8E8] flex flex-col justify-between pb-4 pt-6 px-4 overflow-auto transition-all duration-200 ease-in"
    >
        <div>
            <!-- Mobile Header (Solo menu principale) -->
            <div class="lg:hidden flex items-center justify-end mb-4" x-show="!mobileSubmenuOpen">
                <button
                    @click="mobileMenuOpen = false; mobileSubmenuOpen = false"
                    class="p-2 rounded-md text-gray-500 hover:bg-gray-100"
                    aria-label="Close menu"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @livewire('company-switcher')

            <!-- Navigazione -->
            <nav class="space-y-1 pt-3" x-show="!mobileSubmenuOpen || window.innerWidth >= 1024">

                @if (Auth::user()->admin)
                    <a href="#"
                        @mouseenter="
                            if (mainSidebar && window.innerWidth >= 1024) {
                                clearTimeout(tooltipTimeout);
                                const rect = $event.currentTarget.getBoundingClientRect();
                                tooltipTimeout = setTimeout(() => {
                                    showTooltip = true;
                                    tooltipText = 'Admin';
                                    tooltipX = rect.right + 8;
                                    tooltipY = rect.top + rect.height / 2 - 12;
                                }, 300);
                            }
                        "
                        @mouseleave="
                            clearTimeout(tooltipTimeout);
                            showTooltip = false;
                        "
                                            @click.prevent="handleAdminClick()"
                        class="hover:scale-1015 transform flex space-x-2 items-center py-1.5 text-[#050505] px-1.5 rounded hover:border-[#f5f5f5] mb-2 hover:bg-[#f5f5f5] text-[14px] border transition duration-200 ease-in"
                        :class="selected === 'admin' ? 'text-[#050505] border-[#f5f5f5] bg-[#f5f5f5]' : 'text-[#050505] border-transparent'">

                        <svg class="flex-shrink-0" width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2.08384 4.29424C2.26338 4.16477 2.47593 4.09383 2.68897 4.03377C4.76602 3.44822 6.7364 1.97228 7.36141 1.4745C7.49954 1.3645 7.64235 1.26106 7.80553 1.19361C8.42885 0.935958 9.12856 0.93513 9.75359 1.19313C9.87589 1.24362 9.98634 1.31395 10.0949 1.38952C10.7306 1.83174 13.1395 3.44532 15.2973 4.05941C15.4801 4.11143 15.6619 4.17175 15.8209 4.27588C16.5506 4.75386 17 5.57802 17 6.47134V9.63077C17 12.5155 15.5332 15.1968 13.1187 16.7258L10.1548 18.6026C9.29474 19.1472 8.19952 19.1309 7.35561 18.5609L4.67731 16.7518C2.37993 15.2001 1 12.5877 1 9.79027V6.42418C1 5.57177 1.40935 4.78061 2.08384 4.29424Z" stroke="#282930" stroke-width="1.2" stroke-linecap="round"/>
                            <path d="M13.8636 16.1195C13.6066 14.3384 12.2285 12.8824 10.4343 12.7519C9.41066 12.6775 8.4567 12.6776 7.43114 12.7524C5.63615 12.8832 4.2571 14.3392 4 16.121M11.177 8.25C11.177 9.49264 10.1696 10.5 8.92695 10.5C7.68431 10.5 6.67695 9.49264 6.67695 8.25C6.67695 7.00736 7.68431 6 8.92695 6C10.1696 6 11.177 7.00736 11.177 8.25Z" stroke="#282930" stroke-width="1.2"/>
                        </svg>

                        <span class="truncate block min-w-0">Admin</span>
                    </a>
                @endif

                <!-- Dashboard manuale -->
                <a href="#" 
                    @mouseenter="
                        if (mainSidebar && window.innerWidth >= 1024) {
                            clearTimeout(tooltipTimeout);
                            const rect = $event.currentTarget.getBoundingClientRect();
                            tooltipTimeout = setTimeout(() => {
                                showTooltip = true;
                                tooltipText = 'Dashboard';
                                tooltipX = rect.right + 8;
                                tooltipY = rect.top + rect.height / 2 - 12;
                            }, 300);
                        }
                    "
                    @mouseleave="
                        clearTimeout(tooltipTimeout);
                        showTooltip = false;
                    "
                    @click.prevent="handleDashboardClick()"
                    class="hover:scale-1015 transform flex items-center space-x-2 text-[#050505] py-1.5 px-1.5 rounded hover:border-[#f5f5f5] hover:bg-[#f5f5f5] text-[14px] !mb-5 border transition duration-200 ease-in"
                    :class="selected === 'dashboard' ? 'text-[#050505] border-[#f5f5f5] bg-[#f5f5f5]' : 'text-[#050505] border-transparent'">
                    
                    <!-- Icona -->
                    <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect class="stroke-[#050505]" x="0.6" y="9.21523" width="6.18462" height="6.18462" rx="1.4" stroke-width="1.2"/>
                        <rect class="stroke-[#050505]" x="0.6" y="0.6" width="6.18462" height="6.18462" rx="1.4" stroke-width="1.2"/>
                        <rect class="stroke-[#050505]" x="9.21523" y="9.21523" width="6.18462" height="6.18462" rx="1.4" stroke-width="1.2"/>
                        <rect class="stroke-[#050505]" x="9.21523" y="0.6" width="6.18462" height="6.18462" rx="1.4" stroke-width="1.2"/>
                    </svg>

                    <span class="truncate block min-w-0">Dashboard</span>
                </a>


                <div class="space-y-2 ">
                <template x-for="item in [
                        { name: 'Fatture', value: 'fatture', icon: 'invoice' },
                        { name: 'Spese', value: 'spese', icon: 'spese' },
                        { name: 'Automazioni', value: 'automazioni', icon: 'automazioni', comingSoon: true },
                        { name: 'PEC', value: 'email', icon: 'mail', comingSoon: true },
                        { name: 'Tasse e Tributi', value: 'tasse', icon: 'tasse' },
                        { name: 'Documenti', value: 'documenti', icon: 'documenti' },
                        { name: 'Contatti', value: 'contatti', icon: 'contatti' }
                    ]" :key="item.value">
                        <a href="#"
                            @mouseenter="
                                if (mainSidebar && window.innerWidth >= 1024) {
                                    clearTimeout(tooltipTimeout);
                                    const rect = $event.currentTarget.getBoundingClientRect();
                                    tooltipTimeout = setTimeout(() => {
                                        showTooltip = true;
                                        tooltipText = item.comingSoon ? `${item.name} (In arrivo)` : item.name;
                                        tooltipX = rect.right + 8;
                                        tooltipY = rect.top + rect.height / 2 - 12;
                                    }, 300);
                                }
                            "
                            @mouseleave="
                                clearTimeout(tooltipTimeout);
                                showTooltip = false;
                            "
                            @click.prevent="handleMenuItemClick(item)"
                            class="hover:scale-1015 transform flex items-center py-1.5 px-1.5 rounded text-[14px] border transition duration-200 ease-in relative"
                            :class="[
                                selected === item.value ? 'text-[#050505] border-[#f5f5f5] bg-[#f5f5f5]' : 'text-[#050505] border-transparent',
                                item.comingSoon ? 'text-[#A0A0A0] cursor-not-allowed' : 'hover:bg-[#f5f5f5] hover:border-[#f5f5f5]'
                            ]"
                        >

                            <span
                                x-data="{ showComingSoon: !mainSidebar && item.comingSoon }"
                                x-init="$watch('!mainSidebar', value => {
                                    if (value && item.comingSoon) {
                                        setTimeout(() => showComingSoon = true, 100);
                                    } else {
                                        showComingSoon = false;
                                    }
                                })"
                                x-show="showComingSoon"
                                x-transition.opacity.duration.200ms
                                class="absolute right-0 mt-0.5 mr-1 bg-yellow-300 text-yellow-900 text-[9px] px-1.5 py-0.5 rounded-full leading-tight font-medium uppercase whitespace-nowrap">
                                IN ARRIVO
                            </span>

                            <template x-if="item.icon === 'invoice'">

                                <svg  :class="item.comingSoon ? 'opacity-60 grayscale cursor-not-allowed' : ''" class="flex-shrink-0" width="18" height="17" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_1241_6066)">
                                    <path d="M3.92742 8.85741C3.65316 8.77671 3.46109 8.49957 3.46067 8.18415C3.46193 7.99647 3.52777 7.82124 3.64687 7.69212C3.76974 7.55839 3.92994 7.48507 4.09517 7.48691H5.61873L4.54012 6.3018C4.38076 6.12518 4.31743 5.86695 4.37573 5.62854C4.40508 5.50864 4.4617 5.40074 4.5397 5.31681C4.66215 5.18354 4.81983 5.11022 4.98423 5.11022C4.98548 5.11022 4.98674 5.11022 4.98842 5.11022C5.16036 5.11161 5.3193 5.18401 5.43714 5.31497L7.15277 7.20009L8.86713 5.31358C9.02775 5.13835 9.2626 5.06872 9.47941 5.13282C9.58844 5.1651 9.68658 5.22735 9.7629 5.31312C9.88494 5.4487 9.9512 5.62393 9.95036 5.80654C9.9491 5.9956 9.88326 6.17037 9.76416 6.29995L8.68513 7.48737H10.2154C10.2695 7.48737 10.3265 7.49568 10.3794 7.51135C10.6536 7.59159 10.8457 7.86827 10.8457 8.18415C10.8444 8.37229 10.7786 8.54706 10.6595 8.67618C10.5366 8.80991 10.3789 8.88277 10.2112 8.88185H8.68261L9.76542 10.0716C9.92478 10.2482 9.9881 10.506 9.92981 10.7448C9.90045 10.8647 9.84384 10.9726 9.76584 11.0566C9.64254 11.1907 9.48444 11.2636 9.31711 11.2627C9.14517 11.2613 8.98582 11.1889 8.86839 11.0579L7.15277 9.17282L5.4384 11.0593C5.27778 11.2346 5.04294 11.3042 4.82613 11.2401C4.71709 11.2078 4.61896 11.1456 4.54263 11.0598C4.4206 10.9242 4.35434 10.749 4.35518 10.5664C4.35643 10.3773 4.42228 10.2025 4.54138 10.073L5.6246 8.88139H4.0914C4.0373 8.88139 3.98026 8.87309 3.92742 8.85741Z" fill="#262626"/>
                                    </g>
                                    <path d="M15.1176 16.1865C16.0273 16.1865 17 15.7421 17 14.5978L17 10.7583C17 10.5076 16.7893 10.3044 16.5294 10.3044L13.7059 10.3044M15.1176 16.1865C14.208 16.1865 13.7059 15.4752 13.7059 14.5978L13.7059 10.3044M15.1176 16.1865L3.35294 16.1865C2.05345 16.1865 1 15.1704 1 13.9169L0.999999 1.83887C0.999999 1.461 1 0.99989 1.76456 0.99989L7.66118 0.99989L12.9413 0.999889C13.7059 0.999889 13.7059 1.461 13.7059 1.83887L13.7059 10.3044" stroke="#282930" stroke-width="1.2"/>
                                    <defs>
                                    <clipPath id="clip0_1241_6066">
                                    <rect width="7.38461" height="6.15385" fill="white" transform="matrix(-1 0 0 1 10.8457 5.10938)"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                                    
                            </template>
                            <template x-if="item.icon === 'contatti'">
                                <svg  :class="item.comingSoon ? 'opacity-60 grayscale cursor-not-allowed' : ''" class="flex-shrink-0" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M14.8479 16.5495C14.5433 14.4386 12.9104 12.713 10.7844 12.5584C9.57146 12.4701 8.44108 12.4703 7.22585 12.5589C5.09891 12.7139 3.46483 14.4396 3.16019 16.5513M11.6644 7.22274C11.6644 8.6955 10.4707 9.88941 8.9983 9.88941C7.52585 9.88941 6.3322 8.6955 6.3322 7.22274C6.3322 5.74998 7.52585 4.55607 8.9983 4.55607C10.4707 4.55607 11.6644 5.74998 11.6644 7.22274ZM3.28078 16.7305C7.49277 17.0897 10.4667 17.0905 14.7331 16.7287C15.7928 16.6389 16.6326 15.7944 16.7203 14.734C17.0734 10.4661 17.1079 7.4776 16.7335 3.24512C16.6407 2.19647 15.8066 1.36639 14.7583 1.2756C10.5044 0.907217 7.52692 0.908292 3.23865 1.27788C2.19543 1.36779 1.36239 2.18961 1.26588 3.23274C0.870425 7.50716 0.947165 10.503 1.292 14.7278C1.37877 15.7909 2.21856 16.6399 3.28078 16.7305Z" stroke="#282930" stroke-width="1.2"/>
                                </svg>
                            </template>
                            <template x-if="item.icon === 'spese'">
                                <svg  :class="item.comingSoon ? 'opacity-60 grayscale cursor-not-allowed' : ''" class="flex-shrink-0" width="17" height="19" viewBox="0 0 17 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M13.5005 3.15687C13.5005 2.0776 13.0012 1.18527 11.5196 1C10.4805 1.00063 3.50702 1.00002 3.31879 1H3.31491H3.31484C2.03638 1.00004 1 2.05857 1 3.36433V15.1653C1 15.5589 1.44906 15.7777 1.7522 15.5318L2.97379 14.541C3.1653 14.3856 3.44307 14.4086 3.60714 14.5935L5.05419 16.0138C5.23811 16.221 5.55854 16.221 5.74246 16.0138L7.13761 14.652C7.32153 14.4448 7.64196 14.4448 7.82588 14.652L9.17565 15.9626C9.34523 16.1537 10.2203 15.5197 10.3807 15.3209L10.9025 14.6743C11.0558 14.4522 11.3623 14.4076 11.571 14.5769L12.7483 15.5318C13.0514 15.7777 13.5005 15.5589 13.5005 15.1653V5.95089V3.15687Z" stroke="#282930" stroke-width="1.2"/>
                                    <path d="M16.0007 4.82337C16.0007 3.7441 15.5014 2.85178 14.0198 2.6665C12.9807 2.66714 6.00725 2.66652 5.81901 2.6665H5.81513H5.81506C4.5366 2.66654 3.50022 3.72507 3.50022 5.03083L3.5 16.4913C3.5 16.885 3.94906 17.1038 4.2522 16.8579L5.47379 15.867C5.6653 15.7117 5.94307 15.7347 6.10714 15.9195L7.55419 17.3398C7.73811 17.547 8.05854 17.547 8.24246 17.3398L9.63761 15.978C9.82153 15.7708 10.142 15.7708 10.3259 15.978L11.6756 17.2887C11.8749 17.5132 12.2283 17.4913 12.3991 17.2439L13.4025 16.0003C13.5558 15.7783 13.8623 15.7336 14.071 15.903L15.2483 16.8579C15.5514 17.1038 16.0005 16.885 16.0005 16.4913L16.0007 7.61739V4.82337Z" fill="white" stroke="#282930" stroke-width="1.2"/>
                                    <path d="M6.50977 6.58398H12.9915" stroke="#282930" stroke-width="1.2" stroke-linecap="round"/>
                                    <path d="M6.50977 9.36133H12.9915" stroke="#282930" stroke-width="1.2" stroke-linecap="round"/>
                                    <path d="M6.50977 12.1392H10.9081" stroke="#282930" stroke-width="1.2" stroke-linecap="round"/>
                                </svg>        
                            </template>
                            <template x-if="item.icon === 'automazioni'">
                                <svg  :class="item.comingSoon ? 'opacity-60 grayscale cursor-not-allowed' : ''" class="flex-shrink-0" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1.26588 3.23274C1.36239 2.18961 2.19543 1.36779 3.23865 1.27788C7.52692 0.908292 10.5044 0.907217 14.7583 1.2756C15.8066 1.36639 16.6407 2.19647 16.7335 3.24512C17.1079 7.4776 17.0734 10.4661 16.7203 14.734C16.6326 15.7944 15.7928 16.6389 14.7331 16.7287C10.4667 17.0905 7.49277 17.0897 3.28078 16.7305C2.21856 16.6399 1.37877 15.7909 1.292 14.7278C0.947164 10.503 0.870425 7.50716 1.26588 3.23274Z" stroke="#282930" stroke-width="1.2"/>
                                    <path d="M7.61591 10.1111H5.32771C5.09016 10.1111 4.9317 9.87084 5.02918 9.65845L7.61593 4.37849C7.72166 4.14814 7.95535 4 8.21299 4H10.8426C11.0903 4 11.2483 4.25929 11.1308 4.47312L9.35896 7.69927C9.24152 7.9131 9.39948 8.17238 9.64719 8.17238H11.672C11.9499 8.17238 12.1014 8.49036 11.9231 8.69929L6.58625 14.9531C6.49107 15.0646 6.30991 14.9595 6.36383 14.824L7.92088 10.5487C8.00463 10.3383 7.84632 10.1111 7.61591 10.1111Z" stroke="#282930" stroke-width="1.2"/>
                                </svg>    
                            </template>
                            <template x-if="item.icon === 'tasse'">
                                <svg  :class="item.comingSoon ? 'opacity-60 grayscale cursor-not-allowed' : ''" class="flex-shrink-0"  width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1.26588 3.23274C1.36239 2.18961 2.19543 1.36779 3.23865 1.27788C7.52692 0.908292 10.5044 0.907217 14.7583 1.2756C15.8066 1.36639 16.6407 2.19647 16.7335 3.24512C17.1079 7.4776 17.0734 10.4661 16.7203 14.734C16.6326 15.7944 15.7928 16.6389 14.7331 16.7287C10.4667 17.0905 7.49277 17.0897 3.28078 16.7305C2.21856 16.6399 1.37877 15.7909 1.292 14.7278C0.947165 10.503 0.870425 7.50716 1.26588 3.23274Z" stroke="#282930" stroke-width="1.2"/>
                                    <path d="M6.33398 11.6665L11.2992 6.33304" stroke="black" stroke-width="1.2" stroke-linecap="round"/>
                                    <circle cx="10.6008" cy="11.6668" r="0.6" stroke="black" stroke-width="0.933333"/>
                                    <circle cx="7.40065" cy="6.33327" r="0.6" stroke="black" stroke-width="0.933333"/>
                                </svg>
                            </template>
                            <template x-if="item.icon === 'documenti'">
                                <svg  :class="item.comingSoon ? 'opacity-60 grayscale cursor-not-allowed' : ''" class="flex-shrink-0" width="18" height="16" viewBox="0 0 18 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.40185 15.2154C11.2212 15.2423 12.971 15.1895 15.1109 15.0565C15.994 15.0015 16.6938 14.4854 16.7669 13.8375C17.0611 11.2293 17.0899 9.40298 16.7779 6.81646C16.7315 6.4313 16.4618 6.09438 16.0644 5.87481M9.40185 15.2154C8.19849 15.1976 6.96466 15.1449 5.56732 15.0575C4.68213 15.0022 3.98231 14.4833 3.91 13.8337C3.62264 11.2519 3.55869 9.42104 3.88823 6.8089C3.96866 6.17143 4.66286 5.66921 5.53221 5.61426C9.10577 5.3884 11.587 5.38774 15.1319 5.61287C15.4804 5.635 15.8006 5.72905 16.0644 5.87481M9.40185 15.2154C7.40073 15.2469 5.47671 15.1691 3.15407 14.9827C2.15086 14.9022 1.35773 14.1474 1.27578 13.2025C0.9501 9.44715 0.877623 6.78414 1.25111 2.98466C1.34225 2.05743 2.12902 1.32692 3.11428 1.247C5.14321 1.08243 6.86145 1.00006 8.5762 1C9.10173 0.999983 9.60508 1.20589 9.98682 1.56583L10.8869 2.41451C11.2753 2.78073 11.7893 2.98541 12.324 2.98675L13.9529 2.99086C15.0342 2.99359 15.9418 3.81472 16.0106 4.89004C16.032 5.22432 16.0499 5.55189 16.0644 5.87481" stroke="#282930" stroke-width="1.2" stroke-linecap="round"/>
                                </svg>
                            </template>
                            <template x-if="item.icon === 'mail'">
                                <svg  :class="item.comingSoon ? 'opacity-60 grayscale cursor-not-allowed' : ''" class="flex-shrink-0" width="18" height="15" viewBox="0 0 18 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1.26588 2.86062C1.36239 1.99134 2.19543 1.30649 3.23865 1.23157C7.52692 0.923577 10.5044 0.922681 14.7583 1.22967C15.8066 1.30532 16.6407 1.99706 16.7335 2.87094C17.1079 6.398 17.0734 8.88839 16.7203 12.445C16.6326 13.3286 15.7928 14.0324 14.7331 14.1073C10.4667 14.4088 7.49277 14.4081 3.28078 14.1088C2.21856 14.0333 1.37877 13.3257 1.292 12.4399C0.947165 8.91921 0.870425 6.42263 1.26588 2.86062Z" stroke="#282930" stroke-width="1.2"/>
                                    <path d="M2.06641 2.3335L7.49813 7.62359C8.34773 8.40239 9.65175 8.40239 10.5014 7.62359L15.9331 2.3335" stroke="#282930" stroke-width="1.2" stroke-linecap="round"/>
                                </svg>             
                            </template>
                            <span class="ml-2 truncate block min-w-0" x-text="item.name"></span>
                        </a>
                    </template>
                </div>
            </nav>
            
            <!-- Mobile Submenu Container -->
            <div 
                x-show="mobileSubmenuOpen && window.innerWidth < 1024"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-x-full"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-full"
                class="lg:hidden absolute inset-0 bg-white z-60 overflow-y-auto"
            >
                <!-- Mobile Submenu Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <button
                        @click="mobileSubmenuOpen = false"
                        class="p-2 rounded-md text-gray-500 hover:bg-gray-100"
                        aria-label="Back to main menu"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        @click="mobileMenuOpen = false; mobileSubmenuOpen = false"
                        class="p-2 rounded-md text-gray-500 hover:bg-gray-100"
                        aria-label="Close menu"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="px-4 py-2">
                <!-- Mobile submenu content will be populated from secondary sidebar -->
                
                <!-- Fatture submenu -->
                <div x-show="selected === 'fatture'">
                    <h2 class="text-[16px] font-normal text-[#050505] mb-2 mt-0.5">Fatture</h2>
                    <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Ordinaria</h3>
                    <nav class="space-y-1 text-sm">
                        <a href="{{ route('fatture.lista') }}" wire:navigate @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 {{ request()->routeIs('fatture.*') ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-100' }}">Fatture</a>
                        <a href="#" @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Preventivi</a>
                        <a href="{{ route('note-di-credito.lista') }}" wire:navigate @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 {{ request()->routeIs('note-di-credito.*') ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-100' }}">Note di credito</a>
                        @if (!Auth::user()->current_company->forfettario)
                            <a href="{{ route('autofatture.lista') }}" wire:navigate @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 {{ request()->routeIs('autofatture.*') ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-100' }}">Autofatture</a>
                        @endif
                    </nav>
                    <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Ricorrenti</h3>
                    <nav class="space-y-1 text-sm">
                        <a href="{{ route('fatture-ricorrenti.lista') }}" wire:navigate @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 {{ request()->routeIs('fatture-ricorrenti.*') ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-100' }}">Fatture ricorrenti</a>
                        <a wire:navigate href="{{ route('abbonamenti.lista') }}" @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 {{ request()->routeIs('abbonamenti.lista') ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-100' }}">Abbonamenti</a>
                    </nav>
                    <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Gestione</h3>
                    <nav class="space-y-1 text-sm">
                        <a href="#" @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Prodotti</a>
                        <a href="#" @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Numerazioni</a>
                    </nav>
                </div>
                
                <!-- Contatti submenu -->
                <div x-show="selected === 'contatti'">
                    <h2 class="text-[16px] font-normal text-[#050505] mb-2">Contatti</h2>
                    <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Anagrafiche</h3>
                    <nav class="space-y-1 text-sm">
                        <a href="{{ route('contatti.clienti.lista') }}" wire:navigate @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Clienti</a>
                    </nav>
                </div>
                
                <!-- Email submenu -->
                <div x-show="selected === 'email'">
                    <h2 class="text-[16px] font-normal text-[#050505] mb-2">Email</h2>
                    <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Anagrafiche</h3>
                    <nav class="space-y-1 text-sm">
                        <a href="{{ route('email.list') }}" wire:navigate @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Email</a>
                    </nav>
                </div>
                
                <!-- Documenti submenu -->
                <div x-show="selected === 'documenti'">
                    <h2 class="text-[16px] font-normal text-[#050505] mb-2">Documenti</h2>
                    <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Lavorazioni</h3>
                    <nav class="space-y-1 text-sm">
                        <a href="#" @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Fatture</a>
                        <a href="#" @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Preventivi</a>
                        <a href="#" @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Contratti</a>
                    </nav>
                    <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Spese</h3>
                    <nav class="space-y-1 text-sm">
                        <a href="#" @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Ricevute</a>
                        <a href="#" @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">F24/MAV</a>
                        <a href="#" @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Sanzioni</a>
                    </nav>
                    <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Documentazione</h3>
                    <nav class="space-y-1 text-sm">
                        <a href="#" @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">ADE</a>
                        <a href="#" @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">INPS</a>
                        <a href="#" @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Altro</a>
                    </nav>
                </div>
                
                @if (Auth::user()->admin)
                    <!-- Admin submenu -->
                    <div x-show="selected === 'admin'">
                        <h2 class="text-[16px] font-normal text-[#050505] mb-2">Admin</h2>
                        <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Sito internet</h3>
                        <nav class="space-y-1 text-sm">
                            <a href="{{ route('admin.registrations.index') }}" wire:navigate @click="mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Registrazioni</a>
                        </nav>
                    </div>
                @endif
                
                <!-- Default submenu for unsupported sections -->
                <div x-show="!['fatture', 'contatti', 'email', 'documenti', 'admin'].includes(selected)">
                    <h2 class="text-[16px] font-normal text-[#050505] mb-2 capitalize" x-text="selected"></h2>
                    <p class="text-xs text-gray-500">Nessun sottomenu disponibile</p>
                </div>
                </div>
            </div>

        <!-- Footer -->
        <div class="space-y-3 text-gray-500 mt-16 font-normal text-[14px] px-2 pb-2">
            
            <div 
                x-show="!mainSidebar || window.innerWidth < 1024"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-4"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="space-y-3"
            >
                <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block hover:text-gray-900 truncate">Impostazioni</a>
                <a href="{{ route('company.show') }}" wire:navigate @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block hover:text-gray-900 truncate">Gestione utenti</a>
                <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block hover:text-gray-900 truncate">Integrazioni e partnership</a>
                <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block hover:text-gray-900 truncate">Consiglia Newo</a>
            </div>

            <a href="#" x-show="mainSidebar">
                <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path :class="selected === 'settings' ? 'stroke-[#050505]' : 'stroke-[#050505]'" d="M8 10C9.10457 10 10 9.10457 10 8C10 6.89543 9.10457 6 8 6C6.89543 6 6 6.89543 6 8C6 9.10457 6.89543 10 8 10Z"/>
                    <path :class="selected === 'settings' ? 'stroke-[#050505]' : 'stroke-[#050505]'" d="M9.1766 1.43483C8.93193 1.3335 8.62127 1.3335 7.99993 1.3335C7.3786 1.3335 7.06793 1.3335 6.82327 1.43483C6.66139 1.50184 6.5143 1.60009 6.39042 1.72398C6.26653 1.84786 6.16828 1.99495 6.10127 2.15683C6.03993 2.3055 6.01527 2.4795 6.00593 2.73216C6.00159 2.91478 5.951 3.09332 5.8589 3.25107C5.76679 3.40881 5.63617 3.54062 5.47927 3.63416C5.31981 3.72334 5.14032 3.77061 4.95762 3.77154C4.77492 3.77248 4.59497 3.72704 4.4346 3.6395C4.2106 3.52083 4.0486 3.4555 3.88793 3.43416C3.53748 3.38808 3.18307 3.48303 2.9026 3.69816C2.69327 3.86016 2.53727 4.12883 2.2266 4.66683C1.91593 5.20483 1.75993 5.4735 1.72593 5.73683C1.70302 5.91046 1.71455 6.08691 1.75985 6.25609C1.80514 6.42527 1.88333 6.58387 1.98993 6.72283C2.0886 6.85083 2.2266 6.95816 2.4406 7.09283C2.75593 7.29083 2.9586 7.62816 2.9586 8.00016C2.9586 8.37216 2.75593 8.7095 2.4406 8.90683C2.2266 9.04216 2.08793 9.1495 1.98993 9.2775C1.88333 9.41645 1.80514 9.57505 1.75985 9.74423C1.71455 9.91341 1.70302 10.0899 1.72593 10.2635C1.7606 10.5262 1.91593 10.7955 2.22593 11.3335C2.53727 11.8715 2.6926 12.1402 2.9026 12.3022C3.04156 12.4088 3.20016 12.487 3.36934 12.5323C3.53852 12.5775 3.71497 12.5891 3.8886 12.5662C4.0486 12.5448 4.2106 12.4795 4.4346 12.3608C4.59497 12.2733 4.77492 12.2278 4.95762 12.2288C5.14032 12.2297 5.31981 12.277 5.47927 12.3662C5.80127 12.5528 5.9926 12.8962 6.00593 13.2682C6.01527 13.5215 6.03927 13.6948 6.10127 13.8435C6.16828 14.0054 6.26653 14.1525 6.39042 14.2763C6.5143 14.4002 6.66139 14.4985 6.82327 14.5655C7.06793 14.6668 7.3786 14.6668 7.99993 14.6668C8.62127 14.6668 8.93193 14.6668 9.1766 14.5655C9.33848 14.4985 9.48557 14.4002 9.60945 14.2763C9.73334 14.1525 9.83159 14.0054 9.8986 13.8435C9.95993 13.6948 9.9846 13.5215 9.99393 13.2682C10.0073 12.8962 10.1986 12.5522 10.5206 12.3662C10.6801 12.277 10.8595 12.2297 11.0422 12.2288C11.2249 12.2278 11.4049 12.2733 11.5653 12.3608C11.7893 12.4795 11.9513 12.5448 12.1113 12.5662C12.2849 12.5891 12.4613 12.5775 12.6305 12.5323C12.7997 12.487 12.9583 12.4088 13.0973 12.3022C13.3073 12.1408 13.4626 11.8715 13.7733 11.3335C14.0839 10.7955 14.2399 10.5268 14.2739 10.2635C14.2968 10.0899 14.2853 9.91341 14.24 9.74423C14.1947 9.57505 14.1165 9.41645 14.0099 9.2775C13.9113 9.1495 13.7733 9.04216 13.5593 8.9075C13.4032 8.81243 13.2738 8.67932 13.1832 8.52064C13.0927 8.36195 13.0438 8.18287 13.0413 8.00016C13.0413 7.62816 13.2439 7.29083 13.5593 7.0935C13.7733 6.95816 13.9119 6.85083 14.0099 6.72283C14.1165 6.58387 14.1947 6.42527 14.24 6.25609C14.2853 6.08691 14.2968 5.91046 14.2739 5.73683C14.2393 5.47416 14.0839 5.20483 13.7739 4.66683C13.4626 4.12883 13.3073 3.86016 13.0973 3.69816C12.9583 3.59156 12.7997 3.51337 12.6305 3.46807C12.4613 3.42278 12.2849 3.41125 12.1113 3.43416C11.9513 3.4555 11.7893 3.52083 11.5646 3.6395C11.4043 3.72692 11.2245 3.77229 11.0419 3.77136C10.8593 3.77043 10.68 3.72322 10.5206 3.63416C10.3637 3.54062 10.2331 3.40881 10.141 3.25107C10.0489 3.09332 9.99827 2.91478 9.99393 2.73216C9.9846 2.47883 9.9606 2.3055 9.8986 2.15683C9.83159 1.99495 9.73334 1.84786 9.60945 1.72398C9.48557 1.60009 9.33848 1.50184 9.1766 1.43483Z"/>
                </svg>
            </a>

            <div class="flex justify-between items-center !mt-8">

                <div id="newo-logo" class="flex items-center mb-2">

                    <svg class="origin-center -rotate-[12deg] -ml-[3px] hidden lg:block" x-show="mainSidebar" width="22" height="21" viewBox="0 0 25 21" fill="none" xmlns="http://www.w3.org/2000/svg" x-show="true" class="w-auto h-[21px]">
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
</div>

    <!-- Sidebar secondaria (Desktop only) -->
        <div
            x-show="secondarySidebar"
            :class="secondarySidebar ? 'lg:!left-[65px] left-0' : '' "
            class="hidden lg:block w-full lg:w-[250px] h-full absolute left-0 transition-all duration-200 overflow-hidden lg:border-r border-[#E8E8E8] px-4 pt-16 lg:pt-8 pb-6 bg-white"
        >

        @if (Auth::user()->admin)
            <!-- Admin -->
            <div x-show="selected === 'admin'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transitidivon:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            class="absolute w-[calc(100%-2rem)]">
                    <div>
                        <h2 class="text-[16px] font-normal text-[#050505] mb-2">Admin</h2>
                        <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Sito internet</h3>
                        <nav class="space-y-1 text-sm">
                            <a href="{{ route('admin.registrations.index') }}" 
                               wire:navigate 
                               @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                               class="block rounded py-1.5 px-2 hover:bg-gray-100">Registrazioni</a>
                        </nav>
                    </div>
                </div>
        @endif


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
                    <a href="{{ route('fatture.lista') }}" wire:navigate @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 {{ request()->routeIs('fatture.*') ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-100' }}">Fatture</a>
                    <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Preventivi</a>
                    <a href="{{ route('note-di-credito.lista') }}" wire:navigate @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 {{ request()->routeIs('note-di-credito.*') ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-100' }}">Note di credito</a>
                    @if (!Auth::user()->current_company->forfettario)
                        <a href="{{ route('autofatture.lista') }}" wire:navigate @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 {{ request()->routeIs('autofatture.*') ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-100' }}">Autofatture</a>
                    @endif
                </nav>
                <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Ricorrenti</h3>
                <nav class="space-y-1 text-sm">
                    <a href="{{ route('fatture-ricorrenti.lista') }}" wire:navigate @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 {{ request()->routeIs('fatture-ricorrenti.*') ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-100' }}">Fatture ricorrenti</a>
                    <a wire:navigate href="{{ route('abbonamenti.lista') }}" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 {{ request()->routeIs('abbonamenti.lista') ? 'bg-gray-100 text-gray-900' : 'hover:bg-gray-100' }}">Abbonamenti</a>
                </nav>
                <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Gestione</h3>
                <nav class="space-y-1 text-sm">
                    <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Prodotti</a>
                    <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Numerazioni</a>
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
                    <a href="{{ route('contatti.clienti.lista') }}" wire:navigate @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Clienti</a>
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
                    <a href="{{ route('email.list') }}" wire:navigate @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Email</a>
                </nav>
            </div>
        </div>

        
        <div x-show="selected === 'documenti'" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="absolute w-[calc(100%-2rem)]">
                <div>
                    <h2 class="text-[16px] font-normal text-[#050505] mb-2">Documenti</h2>
                    <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Lavorazioni</h3>
                    <nav class="space-y-1 text-sm">
                        <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Fatture</a>
                        <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Preventivi</a>
                        <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Contratti</a>
                    </nav>
                    <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Spese</h3>
                    <nav class="space-y-1 text-sm">
                        <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Ricevute</a>
                        <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">F24/MAV</a>
                        <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Sanzioni</a>
                    </nav>
                    <h3 class="text-[12px] text-[#616161] mb-2 mt-10">Documentazione</h3>
                    <nav class="space-y-1 text-sm">
                        <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">ADE</a>
                        <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">INPS</a>
                        <a href="#" @click="if (window.innerWidth < 1024) mobileMenuOpen = false" class="block rounded py-1.5 px-2 hover:bg-gray-100">Altro</a>
                    </nav>
                </div>
            </div>


        <!-- Dashboard o fallback -->
        <div x-show="!['fatture', 'contatti', 'email', 'dashboard', 'admin', 'documenti'].includes(selected)" x-transition:enter="transition ease-out duration-200"
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
    <div
        x-show="showTooltip"
        x-text="tooltipText"
        x-transition:enter="transition-opacity duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed px-2 py-1 bg-black text-white text-xs rounded shadow z-[999999]"
        :style="`top: ${tooltipY}px; left: ${tooltipX}px; min-width: max-content;`"
    ></div>
    </div>
</div>
