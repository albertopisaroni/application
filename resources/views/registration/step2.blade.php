<x-guest-layout>
    <form method="POST" action="" class="space-y-6 max-w-md mx-auto mt-10">
        @csrf

        <div>
            <label for="name">Nome</label>
            <input type="text" name="name" required class="w-full rounded-md border px-4 py-2" value="{{ old('name', $registration->name) }}">
        </div>

        <div>
            <label for="surname">Cognome</label>
            <input type="text" name="surname" required class="w-full rounded-md border px-4 py-2" value="{{ old('surname', $registration->surname) }}">
        </div>

        <button type="submit" class="w-full bg-black text-white py-3 rounded-md hover:bg-black/80 transition">Continua</button>
    </form>
</x-guest-layout>