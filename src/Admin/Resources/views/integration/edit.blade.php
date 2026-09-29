<x-admin::layouts>
    <x-slot:title>
        @lang('bagistoapi::app.integration.edit.title')
    </x-slot>

    {!! view_render_event('bagisto.admin.integration.edit.before', ['token' => $token]) !!}

    <!-- Integration Edit Form -->
    <x-admin::form
        method="PUT"
        :action="route('admin.integration.update', $token->id)"
    >
        {!! view_render_event('bagisto.admin.integration.edit.edit_form_controls.before', ['token' => $token]) !!}

        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('bagistoapi::app.integration.edit.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <!-- Back Button -->
                <a
                    href="{{ route('admin.integration.token.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('bagistoapi::app.integration.edit.back-btn')
                </a>

                @unless ($token->isRevoked() || $token->isRegenerated())
                    <!-- Save Button -->
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('bagistoapi::app.integration.edit.save-btn')
                    </button>
                @endunless
            </div>
        </div>

        @include('bagistoapi::integration._form', [
            'token'           => $token,
            'availableAdmins' => $availableAdmins,
            'aclTree'         => $aclTree,
            'plainToken'      => $plainToken,
            'isEdit'          => true,
        ])

        {!! view_render_event('bagisto.admin.integration.edit.edit_form_controls.after', ['token' => $token]) !!}
    </x-admin::form>

    {!! view_render_event('bagisto.admin.integration.edit.after', ['token' => $token]) !!}
</x-admin::layouts>
