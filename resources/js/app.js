import './bootstrap';

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

document.addEventListener('DOMContentLoaded', () => {
    const isMac = navigator.platform.toUpperCase().includes('MAC');
    const shortcutHint = document.getElementById('shortcutHint');

    if (shortcutHint) {
        shortcutHint.textContent = isMac ? '⌘K' : 'Ctrl+K';
    }
});