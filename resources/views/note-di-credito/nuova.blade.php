<x-app-layout>
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-4">Crea Nuova Nota di Credito</h1>

    @if(session('success'))
      <div class="bg-green-200 text-green-800 p-3 rounded mb-4">
         {{ session('success') }}
      </div>
    @endif

    @livewire('credit-note-form')
</div>
</x-app-layout> 