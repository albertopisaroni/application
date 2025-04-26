<x-app-layout>
    <div class="max-w-3xl mx-auto py-10">
        <h1 class="text-2xl font-bold mb-6">Modifica Contatto</h1>

        <form method="POST" action="{{ route('contatti.clienti.contact.update', $contact) }}" class="bg-white p-6 rounded shadow grid grid-cols-2 gap-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block mb-1 font-medium">Nome</label>
                <input type="text" name="name" value="{{ old('name', $contact->name) }}" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block mb-1 font-medium">Cognome</label>
                <input type="text" name="surname" value="{{ old('surname', $contact->surname) }}" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block mb-1 font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email', $contact->email) }}" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block mb-1 font-medium">Telefono</label>
                <input type="text" name="phone" value="{{ old('phone', $contact->phone) }}" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block mb-1 font-medium">Ruolo</label>
                <input type="text" name="role" value="{{ old('role', $contact->role) }}" class="w-full border rounded px-3 py-2">
            </div>
            <div class="col-span-2 flex items-center space-x-4 mt-4">
                <label>
                    <input type="checkbox" name="receives_invoice_copy" value="1" {{ $contact->receives_invoice_copy ? 'checked' : '' }}>
                    Copia fatture
                </label>
                <label>
                    <input type="checkbox" name="receives_notifications" value="1" {{ $contact->receives_notifications ? 'checked' : '' }}>
                    Notifiche
                </label>
            </div>

            <div class="col-span-2 text-right mt-4">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salva modifiche</button>
            </div>
        </form>
    </div>
</x-app-layout>