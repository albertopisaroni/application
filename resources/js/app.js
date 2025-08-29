import './bootstrap';

import { initDashboardCharts } from './charts';


window.addEventListener('company-switched', () => {
    Livewire.navigate(window.location.href);
});

document.addEventListener('keydown', function (e) {
    const input = document.getElementById('searchInput');
    if (!input) return;

    const isMac = navigator.platform.toUpperCase().includes('MAC');
    const isK = e.key.toLowerCase() === 'k';
    const isModifierPressed = isMac ? e.metaKey : e.ctrlKey;

    if (isModifierPressed && isK) {
        e.preventDefault();

        if (document.activeElement === input) {
            // Se ha già il focus, seleziona tutto
            input.select();
        } else {
            // Altrimenti dai il focus
            input.focus();
        }
    }
});




document.addEventListener('DOMContentLoaded', () => {;
    
    const isMac = navigator.platform.toUpperCase().includes('MAC');
    const shortcutHint = document.getElementById('shortcutHint');
    
    if (shortcutHint) {
        shortcutHint.textContent = isMac ? '⌘K' : 'Ctrl+K';
    }

    initDashboardCharts();

    // Riassegna dopo ogni livewire:navigate
    document.addEventListener('livewire:navigated', () => {
        initDashboardCharts();
    });

    // Riassegna quando i dati del dashboard cambiano
    document.addEventListener('livewire:update', () => {
        if (window.location.pathname === '/') {
            setTimeout(() => {
                initDashboardCharts();
            }, 100);
        }
    });

    // Riassegna quando il componente viene aggiornato (Livewire v3)
    document.addEventListener('livewire:updated', () => {
        if (window.location.pathname === '/') {
            setTimeout(() => {
                initDashboardCharts();
            }, 100);
        }
    });

    // Riassegna quando il componente viene reidratato
    document.addEventListener('livewire:rehydrated', () => {
        if (window.location.pathname === '/') {
            setTimeout(() => {
                initDashboardCharts();
            }, 100);
        }
    });

    // Listener per l'evento charts-updated
    document.addEventListener('charts-updated', () => {
        console.log('Charts updated event received');
        setTimeout(() => {
            initDashboardCharts();
        }, 100);
    });

    // Listener per evitare conflitti con Livewire
    document.addEventListener('livewire:init', () => {
        console.log('Livewire initialized');
    });
});