<x-admin::layouts.anonymous>
    <x-slot:title>
        @lang('bagistoapi::app.integration.revoke-confirmation.title')
    </x-slot>

    <div class="flex h-[100vh] items-center justify-center">
        <div class="flex flex-col items-center gap-5">
            @if ($logo = core()->getConfigData('general.design.admin_logo.logo_image'))
                <img
                    class="h-10 w-[110px]"
                    src="{{ Storage::url($logo) }}"
                    alt="{{ config('app.name') }}"
                />
            @else
                <img
                    class="w-max"
                    src="{{ bagisto_asset('images/logo.svg') }}"
                    alt="{{ config('app.name') }}"
                />
            @endif

            <div class="box-shadow flex w-[400px] max-w-[calc(100vw-32px)] flex-col rounded-md bg-white dark:bg-gray-900">
                @if ($alreadyInactive)
                    <div class="flex items-center gap-2.5 p-4">
                        <span class="icon-information text-2xl text-yellow-600"></span>

                        <p class="text-xl font-bold text-gray-800 dark:text-white">
                            @lang('bagistoapi::app.integration.revoke-confirmation.already-inactive-title')
                        </p>
                    </div>

                    <p class="border-t p-4 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        @lang('bagistoapi::app.integration.revoke-confirmation.already-inactive-message', ['name' => $token->name])
                    </p>
                @else
                    <div class="flex items-center gap-2.5 p-4">
                        <span class="icon-done text-2xl text-green-600"></span>

                        <p class="text-xl font-bold text-gray-800 dark:text-white">
                            @lang('bagistoapi::app.integration.revoke-confirmation.success-title')
                        </p>
                    </div>

                    <p class="border-t p-4 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        @lang('bagistoapi::app.integration.revoke-confirmation.success-message', ['name' => $token->name])
                    </p>
                @endif
            </div>

            <div class="text-sm font-normal">
                @lang('admin::app.users.sessions.powered-by-description', [
                    'bagisto' => '<a class="text-blue-600 hover:underline" href="https://bagisto.com/en/">Bagisto</a>',
                    'webkul' => '<a class="text-blue-600 hover:underline" href="https://webkul.com/">Webkul</a>',
                ])
            </div>
        </div>
    </div>
</x-admin::layouts.anonymous>
