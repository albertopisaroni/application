<x-app-layout>

    <livewire:subscription-list />

    <a href="{{ route('stripe.connect') }}" class="btn btn-primary">
        Collega il tuo account Stripe
    </a>
    
</x-app-layout>