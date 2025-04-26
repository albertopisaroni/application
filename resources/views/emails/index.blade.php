<x-app-layout>
        @livewire('email-list', ['emailAccountId' => $account->id], key($account->id))
</x-app-layout>