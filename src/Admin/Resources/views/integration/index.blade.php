<x-admin::layouts>
    <x-slot:title>
        @lang('bagistoapi::app.integration.index.title')
    </x-slot>

    {!! view_render_event('bagisto.admin.integration.index.before') !!}

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('bagistoapi::app.integration.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <!-- Create Button -->
            @if (bouncer()->hasPermission('integration.create'))
                <a
                    href="{{ route('admin.integration.create') }}"
                    class="primary-button"
                >
                    @lang('bagistoapi::app.integration.index.create-btn')
                </a>
            @endif
        </div>
    </div>

    {!! view_render_event('bagisto.admin.integration.list.before') !!}

    <x-admin::datagrid :src="route('admin.integration.token.index')" />

    {!! view_render_event('bagisto.admin.integration.list.after') !!}

    {!! view_render_event('bagisto.admin.integration.index.after') !!}
</x-admin::layouts>
