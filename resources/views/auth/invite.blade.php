<x-guest-layout>
    <div class="max-w-md mx-auto mt-10 bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Completa il tuo accesso</h1>

        <p class="mb-4">Ciao {{ $user->name ?? 'nuovo utente' }}, sei stato invitato a unirti a <strong>{{ $user->companies()->first()->name ?? 'un\'azienda' }}</strong>.</p>

        <form method="POST" action="{{ route('invitation.accept', $user->invitation_token) }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ $user->email }}" readonly class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Nuova password</label>
                <input type="password" name="password" required class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">Conferma password</label>
                <input type="password" name="password_confirmation" required class="w-full border rounded px-3 py-2">
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Attiva il tuo account
            </button>
        </form>
    </div>
</x-guest-layout>