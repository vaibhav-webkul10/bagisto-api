<x-admin::layouts>
    <x-slot:title>
        @lang('bagistoapi::app.integration.create.title')
    </x-slot>

    {!! view_render_event('bagisto.admin.integration.create.before') !!}

    <!-- Integration Create Form -->
    <x-admin::form :action="route('admin.integration.store')">
        {!! view_render_event('bagisto.admin.integration.create.create_form_controls.before') !!}

        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('bagistoapi::app.integration.create.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <!-- Back Button -->
                <a
                    href="{{ route('admin.integration.token.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('bagistoapi::app.integration.create.back-btn')
                </a>

                <!-- Save Button -->
                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('bagistoapi::app.integration.create.save-btn')
                </button>
            </div>
        </div>

        @include('bagistoapi::integration._form', [
            'token'           => null,
            'availableAdmins' => $availableAdmins,
            'aclTree'         => $aclTree,
            'plainToken'      => null,
            'isEdit'          => false,
        ])

        {!! view_render_event('bagisto.admin.integration.create.create_form_controls.after') !!}
    </x-admin::form>

    {!! view_render_event('bagisto.admin.integration.create.after') !!}
</x-admin::layouts>
