<div>
    <select wire:model="current" wire:change="switchCompany($event.target.value)">
        @foreach ($companies as $company)
            <option value="{{ $company->id }}">{{ $company->name }}</option>
        @endforeach
    </select>
</div>