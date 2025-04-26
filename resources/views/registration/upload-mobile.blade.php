<x-guest-layout>
    <div class="text-center mt-10 space-y-6">
        <h1 class="text-xl font-bold">Carica il tuo documento</h1>
        <p class="text-gray-500">Per la registrazione associata a <code>{{ $uuid }}</code></p>

        <form action="#" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="document" class="block w-full">
            <button class="mt-4 bg-black text-white py-2 px-6 rounded-lg">Carica</button>
        </form>
    </div>
</x-guest-layout>