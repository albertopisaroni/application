import './bootstrap';

window.addEventListener('company-switched', () => {
    Livewire.navigate(window.location.href);
});
