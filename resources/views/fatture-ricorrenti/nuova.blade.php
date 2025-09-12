<x-app-layout>
    <div class="container mx-auto py-8">
        @if(session('success'))
            <div class="bg-green-200 text-green-800 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        @livewire('recurring-invoice-form')
    </div>
</x-app-layout>