<x-guest-layout>
<div class="max-w-[90%]">

                

        <form action="" method="post">
            @csrf
            

        
            <div class="flex items-center mt-5 relative">

                <span class="ml-px trasition-all ease-out absolute text-gray-400 px-4 pointer-events-none text-xs top-2 [&amp;:has(+_input:placeholder-shown)]:text-base [&amp;:has(+_input:placeholder-shown)]:top-[unset] font-WF-Visual-Sans-Text">Inserisci la tua email aziendale</span>
                <input type="email" required="" placeholder="" name="email" class="transition-colors duration-200 text-base px-4 w-full border border-button-border-gray hover:border-button-border-gray-hover font-WF-Visual-Sans-Text focus:border-button-border-gray-hover rounded-[20px] focus:outline-none text-dark-1 bg-gray-light pt-6 pb-2 [&amp;:placeholder-shown]:py-4">
                <button type="submit" class="absolute right-0 flex items-center justify-center gap-2 text-center text-lg/[25px] font-soehne bg-black min-w-16 py-4 px-8 text-white font-medium whitespace-nowrap rounded-[20px] w-full sm:w-auto duration-200 shadow-custom hover:bg-black-hover">
                    <span>Iscriviti</span> 
                </button>
            </div>

        </form>

        <div class="relative mt-6">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm font-medium leading-6">
                <span class="font-WF-Visual-Sans-Text bg-white px-6 text-gray-900">Oppure</span>
            </div>
        </div>

        <div class="flex space-x-4 mt-6">

            <div id="g_id_onload"
                data-client_id="{{ config('services.google.client_id') }}"
                data-login_uri="{{ config('services.google.redirect') }}"
                data-auto_prompt="true"
                data-auto_select="true"
                data-itp_support="true"
                data-ux_mode="redirect">
            </div>

            <div class="g_id_signin"
                data-type="standard"
                data-shape="rectangular"
                data-theme="outline"
                data-text="continue_with"
                data-size="large"
                data-logo_alignment="left">
            </div>

            <a href="{{ route('social.redirect', ['provider' => 'microsoft', 'origin' => 'start']) }}" class="text-b`ase/[145%] inline-block min-w-16 px-[18px] py-2.5 text-dark-1 rounded-[20px] font-medium transition-all hover:rotate-[-1deg] border border-button-border-gray hover:border-button-border-gray-hover duration-200 flex w-full justify-center">
                <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" alt="Microsoft" class="h-6 w-6 mr-2">
                <span>Iscriviti con Microsoft</span>
            </a>
        </div>

    </div>

            
</x-guest-layout>