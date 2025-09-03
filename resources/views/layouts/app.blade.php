@php
    $path = request()->path();

    $selected = match (true) {
        (str_starts_with($path, 'fatture') || str_starts_with($path, 'fatture-ricorrenti') || str_starts_with($path, 'abbonamenti') || str_starts_with($path, 'note-di-credito') || str_starts_with($path, 'autofatture')) => 'fatture',
        str_starts_with($path, 'contatti') => 'contatti',
        str_starts_with($path, 'email') => 'email',
        str_starts_with($path, 'spese') => 'spese',
        str_starts_with($path, 'tasse') => 'tasse',
        str_starts_with($path, 'documenti') => 'documenti',
        str_starts_with($path, 'automazioni') => 'automazioni',
        str_starts_with($path, 'admin') => 'admin',
        default => 'dashboard',
    };

    $mainSidebar = ($selected !== 'dashboard' && $selected !== 'spese' && $selected !== 'tasse') ? 'true' : 'false';
    $secondarySidebar = ($selected !== 'dashboard' && $selected !== 'spese' && $selected !== 'tasse') ? 'true' : 'false';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white antialiased">
    <head>
        <title>{{ config('app.name', 'Newo - Il Fisco, ma intelligente') }}</title>

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="robots" content="index, follow">
        <meta name="slack-app-id" content="">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:locale" content="it_IT">
        <meta property="og:type" content="website">

        <link rel="icon" href="/favicon.ico" type="image/x-icon">
        <link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="60x60" href="/apple-icon-60x60.png">
        <link rel="apple-touch-icon" sizes="72x72" href="/apple-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="76x76" href="/apple-icon-76x76.png">
        <link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png">
        <link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png">
        <link rel="apple-touch-icon" sizes="144x144" href="/apple-icon-144x144.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-icon-180x180.png">
        <link rel="icon" type="image/png" sizes="192x192"  href="/android-icon-192x192.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="manifest" href="/manifest.json">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <style>
            [x-cloak] { display: none !important; }
        </style>

    </head>

    <body class="h-full"
    x-data="{ 
        selected: '{{ $selected }}', 
        mainSidebar: {{ $mainSidebar }}, 
        secondarySidebar: {{ $secondarySidebar }},
        animateBars: false,
        tooltipText: '', 
        tooltipX: 0, 
        tooltipY: 0, 
        showTooltip: false,
        tooltipTimeout: null,
        mobileMenuOpen: false,
        mobileSubmenuOpen: false,
        handleAdminClick() {
            // Se siamo già su admin, chiudi la sidebar secondaria
            if (this.selected === 'admin') {
                this.selected = 'admin';
                if (window.innerWidth >= 1024) {
                    this.mainSidebar = true;
                    this.secondarySidebar = false; // Chiudi la sidebar secondaria
                } else {
                    this.mobileSubmenuOpen = false;
                }
            } else {
                // Naviga a admin
                this.selected = 'admin';
                if (window.innerWidth >= 1024) {
                    this.mainSidebar = true;
                    this.secondarySidebar = true;
                } else {
                    this.mobileSubmenuOpen = true;
                }
            }
            this.showTooltip = false;
            this.animateBars = true;
            setTimeout(() => this.animateBars = false, 500);
        },
        handleDashboardClick() {
            this.showTooltip = false;
            
            // Non navigare se siamo già sulla dashboard
            if (window.location.pathname === '/') {
                if (window.innerWidth < 1024) {
                    this.mobileMenuOpen = false;
                }
                return;
            }
            
            // Solo se non siamo sulla dashboard, naviga
            this.selected = 'dashboard'; 
            if (window.innerWidth >= 1024) {
                this.mainSidebar = false; 
                this.secondarySidebar = false; // Chiudi sempre la sidebar secondaria per dashboard
            } else {
                this.mobileSubmenuOpen = false;
            }
            this.animateBars = true; 
            setTimeout(() => {
                Livewire.navigate('/');
                if (window.innerWidth < 1024) {
                    this.mobileMenuOpen = false;
                }
                this.animateBars = false;
            }, 200);
        },
        handleMenuItemClick(item) {
            if (!item.comingSoon) {
                // Spese si comporta come Dashboard - navigazione diretta
                if (item.value === 'spese') {
                    if (window.innerWidth >= 1024) {
                        this.mainSidebar = false; 
                        this.secondarySidebar = false; // Chiudi sempre la sidebar secondaria per spese
                    } else {
                        this.mobileSubmenuOpen = false;
                    }
                    
                    this.selected = item.value;
                    this.animateBars = true;

                    // Non navigare se siamo già su spese (controlla URL)
                    if (window.location.pathname.startsWith('/spese')) {
                        if (window.innerWidth < 1024) {
                            this.mobileMenuOpen = false;
                        }
                        return;
                    }

                    setTimeout(() => {
                        Livewire.navigate('/spese');
                        if (window.innerWidth < 1024) {
                            this.mobileMenuOpen = false;
                        }
                        this.animateBars = false;
                    }, 200);
                } else if (item.value === 'tasse') {
                    this.selected = item.value;
                    if (window.innerWidth >= 1024) {
                        this.mainSidebar = false; 
                        this.secondarySidebar = false; // Chiudi sempre la sidebar secondaria per tasse
                    } else {
                        this.mobileSubmenuOpen = false;
                    }
                    this.animateBars = true;

                    // Non navigare se siamo già su tasse (controlla URL)
                    if (window.location.pathname.startsWith('/tasse')) {
                        if (window.innerWidth < 1024) {
                            this.mobileMenuOpen = false;
                        }
                        return;
                    }
                        
                    setTimeout(() => {
                        Livewire.navigate('/tasse');
                        if (window.innerWidth < 1024) {
                            this.mobileMenuOpen = false;
                        }
                        this.animateBars = false;
                    }, 200);
                } else {
                    // Comportamento normale per gli altri menu
                    // Se siamo già sulla stessa sezione, chiudi la sidebar secondaria
                    if (this.selected === item.value) {
                        this.selected = item.value;
                        if (window.innerWidth >= 1024) {
                            this.mainSidebar = true;
                            this.secondarySidebar = false; // Chiudi la sidebar secondaria
                        } else {
                            this.mobileSubmenuOpen = false;
                        }
                    } else {
                        // Naviga a una nuova sezione
                        this.selected = item.value;
                        if (window.innerWidth >= 1024) {
                            this.mainSidebar = true;
                            this.secondarySidebar = true;
                        } else if (item.value !== 'dashboard') {
                            this.mobileSubmenuOpen = true;
                        }
                    }
                    this.animateBars = true;
                    setTimeout(() => this.animateBars = false, 500);
                }
            }
        }
    }"
    x-effect="document.body.style.overflow = mobileMenuOpen && window.innerWidth < 1024 ? 'hidden' : 'auto'">

        
        @include('partials/sidebar')   

        <div 
            :class="secondarySidebar ? 'lg:pl-[315px]' : 'lg:pl-[250px]'"
            class="transition-all duration-200 ease-in-out"
        >

            <main class="p-4 lg:p-8">
                {{ $slot }}

                @if (session('status') !== null)
                    @if(session('status') == 'swal') 
                        <script> setTimeout(function(){ Swal.fire({ title: {!! json_encode(session('title')) !!}, text: {!! json_encode(session('text')) !!}, icon: {!! json_encode(session('icon')) !!}, confirmButtonText: 'Chiudi' }); }, 1); </script> 
                    @endif
                @endif
            </main>

        </div>

        @livewireScripts
    </body>
</html>
