<div x-data="{ open: false }" @click.outside="open = false">
    {{-- Bottone visibile --}}
    <button @click="open = !open" type="button" class="flex items-center mb-6 space-x-2 my-1 w-full cursor-pointer">
        <img class="w-8 h-8 rounded-full" alt="" src="{{ Auth::user()->currentCompany?->logo }}">
        <span class="text-sm font-normal uppercase overflow-hidden text-ellipsis whitespace-nowrap mx-2 !mr-4">{{ Auth::user()->currentCompany?->name }}</span>
        <svg class="size-3.5 absolute right-4 mt-[2px] mr-1" :class="mainSidebar ? 'hidden' : '' " xmlns="http://www.w3.org/2000/svg" width="8" height="8" fill="currentColor" viewBox="0 0 8 8" class="cMc"><path d="M5.493 1.999a.234.234 0 01-.175-.075L3.998.604l-1.323 1.32c-.1.1-.255.1-.355 0-.1-.1-.1-.255 0-.355L3.819.075c.1-.1.255-.1.355 0l1.499 1.499c.1.1.1.255 0 .355a.251.251 0 01-.175.075l-.005-.005zm0 3.997a.234.234 0 00-.175.075l-1.32 1.32L2.68 6.07c-.1-.1-.255-.1-.355 0-.1.1-.1.255 0 .355l1.499 1.499c.1.1.255.1.355 0l1.499-1.499c.1-.1.1-.255 0-.355a.251.251 0 00-.175-.075h-.01z"></path></svg>
    </button>


    {{-- Dropdown --}}
    <div x-show="open" x-transition class="absolute z-50 w-[calc(100%-2rem)] mt-1 bg-white rounded-md shadow-lg">
        @foreach ($companies as $company)
            <button
                wire:click="switchCompany({{ $company->id }})"
                @click="open = false"
                class="flex items-center w-full px-3 py-2 text-sm text-left hover:bg-gray-100 transition"
            >
                <img class="w-6 h-6 rounded-full mr-2" src="{{ $company->logo ?? '/default-logo.png' }}" alt="">
                <span class="truncate">{{ $company->name }}</span>
            </button>
        @endforeach
    </div>
</div>