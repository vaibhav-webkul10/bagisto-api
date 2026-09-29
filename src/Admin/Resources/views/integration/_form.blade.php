@php
    $permissionType = old('permission_type', $token?->permission_type ?? 'custom');
    $abilities      = old('permissions', $token?->abilities ?? []);
    $selectedAdmin  = old('admin_id', $token?->admin_id);
    $name           = old('name', $token?->name);
    $description    = old('description', $token?->description);

    $tokenIsActive   = $token && $token->isActive();
    $tokenIsDraft    = $token && $token->isDraft();
    $tokenIsHistoric = $token && ($token->isRevoked() || $token->isRegenerated());

    $expiresAt = $token?->expires_at;
    $rateMin   = $token?->rate_limit_per_minute;
    $rateDay   = $token?->rate_limit_per_day;

    $expiresMode = old('expires_mode', $expiresAt ? 'expires' : ($tokenIsActive ? 'expires' : 'never'));
    $rateMinMode = old('rate_min_mode', $rateMin !== null ? 'limited' : ($tokenIsActive ? 'limited' : 'unlimited'));
    $rateDayMode = old('rate_day_mode', $rateDay !== null ? 'limited' : ($tokenIsActive ? 'limited' : 'unlimited'));

    $allowedIps     = $token?->allowed_ips ?? [];
    $ipMode         = old('ip_mode', ! empty($allowedIps) ? 'restricted' : 'any');
    $allowedIpsText = old('allowed_ips_text', is_array($allowedIps) ? implode("\n", $allowedIps) : '');
@endphp

<div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
    <!-- Access Control -->
    <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                @lang('bagistoapi::app.integration.fields.access-control')
            </p>

            <v-integration-access-control>
                <div class="mb-4">
                    <div class="shimmer mb-1.5 h-4 w-24"></div>
                    <div class="custom-select h-11 w-full rounded-md border bg-white px-3 py-2.5 text-sm font-normal text-gray-600 transition-all dark:border-gray-800 dark:bg-gray-900"></div>
                </div>

                <x-admin::shimmer.tree />
            </v-integration-access-control>
        </div>
    </div>

    <!-- General, Token and Token Settings -->
    <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">

        <!-- General -->
        <x-admin::accordion>
            <x-slot:header>
                <div class="flex items-center justify-between">
                    <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('bagistoapi::app.integration.fields.general')
                    </p>
                </div>
            </x-slot>

            <x-slot:content>
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('bagistoapi::app.integration.fields.name')
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="text"
                        id="name"
                        name="name"
                        rules="required"
                        :value="$name"
                        :label="trans('bagistoapi::app.integration.fields.name')"
                        :placeholder="trans('bagistoapi::app.integration.fields.name')"
                        :disabled="$tokenIsHistoric"
                    />
                    <x-admin::form.control-group.error control-name="name" />
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('bagistoapi::app.integration.fields.description')
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="textarea"
                        id="description"
                        name="description"
                        :value="$description"
                        :label="trans('bagistoapi::app.integration.fields.description')"
                        :placeholder="trans('bagistoapi::app.integration.fields.description')"
                        :disabled="$tokenIsHistoric"
                    />
                    <x-admin::form.control-group.error control-name="description" />
                </x-admin::form.control-group>

                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label class="required">
                        @lang('bagistoapi::app.integration.fields.assign-user')
                    </x-admin::form.control-group.label>

                    @if ($isEdit)
                        <input type="hidden" name="admin_id" value="{{ $token?->admin_id }}" />
                        <input
                            type="text"
                            disabled
                            value="{{ $token?->admin?->name }} ({{ $token?->admin?->email }})"
                            class="w-full cursor-not-allowed rounded-md border bg-gray-100 px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300"
                        />
                    @else
                        @if ($availableAdmins->isEmpty())
                            <p class="text-sm text-red-600">
                                @lang('bagistoapi::app.integration.fields.no-available-admins')
                            </p>
                        @else
                            <x-admin::form.control-group.control
                                type="select"
                                name="admin_id"
                                id="admin_id"
                                rules="required"
                                :label="trans('bagistoapi::app.integration.fields.assign-user')"
                            >
                                <option value="">
                                    @lang('bagistoapi::app.integration.fields.select-admin')
                                </option>
                                @foreach ($availableAdmins as $availableAdmin)
                                    <option value="{{ $availableAdmin->id }}" @selected((int) $selectedAdmin === (int) $availableAdmin->id)>
                                        {{ $availableAdmin->name }} ({{ $availableAdmin->email }})
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>
                        @endif
                        <x-admin::form.control-group.error control-name="admin_id" />
                    @endif
                </x-admin::form.control-group>
            </x-slot>
        </x-admin::accordion>

        @if ($isEdit)
            <!-- Token -->
            <x-admin::accordion>
                <x-slot:header>
                    <div class="flex items-center justify-between">
                        <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('bagistoapi::app.integration.edit.token-label')
                        </p>

                        <span class="{{ $tokenIsActive ? 'label-active' : ($tokenIsDraft ? 'label-pending' : 'label-canceled') }}">
                            @lang('bagistoapi::app.integration.status.'.$token->status)
                        </span>
                    </div>
                </x-slot>

                <x-slot:content>
                    @if ($plainToken)
                        <div class="rounded border border-green-300 bg-green-50 p-3 dark:border-green-800 dark:bg-gray-800">
                            <p class="mb-2 flex items-center gap-1 text-xs font-semibold text-green-700 dark:text-green-400">
                                <span class="icon-information text-lg"></span>

                                @lang('bagistoapi::app.integration.edit.token-warning')
                            </p>

                            <div class="flex items-center gap-2">
                                <code class="flex-1 break-all rounded-md border bg-white px-3 py-2.5 text-xs text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">{{ $plainToken }}</code>

                                <v-integration-token-copy :value="@js($plainToken)"></v-integration-token-copy>
                            </div>
                        </div>
                    @elseif ($tokenIsActive)
                        <div class="rounded border bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800">
                            <code class="block break-all text-sm text-gray-800 dark:text-white">{{ $token->id }}|{{ $token->token_preview }}...xxxx</code>

                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                @lang('bagistoapi::app.integration.edit.masked')
                            </p>
                        </div>
                    @elseif ($tokenIsDraft)
                        <div class="rounded border bg-gray-50 p-3 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300">
                            @lang('bagistoapi::app.integration.edit.not-generated')
                        </div>
                    @else
                        <div class="rounded border bg-gray-50 p-3 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300">
                            @lang('bagistoapi::app.integration.edit.history-banner')

                            @if ($token->isRegenerated() && $token->regenerated_to_id)
                                <a
                                    href="{{ route('admin.integration.edit', $token->regenerated_to_id) }}"
                                    class="text-blue-600 hover:underline"
                                >
                                    @lang('bagistoapi::app.integration.edit.view-successor')
                                </a>
                            @endif
                        </div>
                    @endif

                    @unless ($tokenIsHistoric)
                        <v-integration-token-actions></v-integration-token-actions>
                    @endunless
                </x-slot>
            </x-admin::accordion>

            <!-- Token Settings -->
            <x-admin::accordion>
                <x-slot:header>
                    <div class="flex items-center justify-between">
                        <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('bagistoapi::app.integration.fields.token-settings')
                        </p>
                    </div>
                </x-slot>

                <x-slot:content>
                    <v-integration-token-settings>
                        <x-admin::shimmer.tree />
                    </v-integration-token-settings>
                </x-slot>
            </x-admin::accordion>
        @endif
    </div>
</div>

@if ($isEdit)
    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-integration-token-settings-template"
        >
            <div>
                <!-- Valid Till -->
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('bagistoapi::app.integration.fields.valid-till')
                    </x-admin::form.control-group.label>

                    <div class="flex flex-col gap-2">
                        <label class="flex w-max cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input
                                type="radio"
                                name="expires_mode"
                                value="never"
                                class="peer sr-only"
                                v-model="expiresMode"
                                :disabled="disabled"
                            />

                            <span class="icon-radio-normal text-2xl peer-checked:icon-radio-selected peer-checked:text-blue-600"></span>

                            @lang('bagistoapi::app.integration.fields.never-expires')
                        </label>

                        <label class="flex w-max cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input
                                type="radio"
                                name="expires_mode"
                                value="expires"
                                class="peer sr-only"
                                v-model="expiresMode"
                                :disabled="disabled"
                            />

                            <span class="icon-radio-normal text-2xl peer-checked:icon-radio-selected peer-checked:text-blue-600"></span>

                            @lang('bagistoapi::app.integration.fields.expires-on')
                        </label>

                        <div v-show="expiresMode === 'expires'">
                            <x-admin::flat-picker.date>
                                <input
                                    type="text"
                                    name="expires_at"
                                    v-model="expiresAt"
                                    :disabled="disabled"
                                    autocomplete="off"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                    placeholder="@lang('bagistoapi::app.integration.fields.expires-on')"
                                />
                            </x-admin::flat-picker.date>
                        </div>
                    </div>

                    @error('expires_at')
                        <p class="mt-1 text-xs italic text-red-600">{{ $message }}</p>
                    @enderror
                </x-admin::form.control-group>

                <!-- Rate Limits -->
                <x-admin::form.control-group v-for="limit in limits" ::key="limit.name">
                    <x-admin::form.control-group.label>
                        @{{ limit.label }}
                    </x-admin::form.control-group.label>

                    <div class="flex flex-col gap-2">
                        <label class="flex w-max cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input
                                type="radio"
                                :name="limit.modeName"
                                value="unlimited"
                                class="peer sr-only"
                                v-model="limit.mode"
                                :disabled="disabled"
                            />

                            <span class="icon-radio-normal text-2xl peer-checked:icon-radio-selected peer-checked:text-blue-600"></span>

                            @lang('bagistoapi::app.integration.fields.unlimited')
                        </label>

                        <label class="flex w-max cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input
                                type="radio"
                                :name="limit.modeName"
                                value="limited"
                                class="peer sr-only"
                                v-model="limit.mode"
                                :disabled="disabled"
                            />

                            <span class="icon-radio-normal text-2xl peer-checked:icon-radio-selected peer-checked:text-blue-600"></span>

                            @lang('bagistoapi::app.integration.fields.limit-to')
                        </label>

                        <div
                            class="flex items-center gap-2"
                            v-show="limit.mode === 'limited'"
                        >
                            <input
                                type="number"
                                min="1"
                                :name="limit.name"
                                v-model="limit.value"
                                :disabled="disabled"
                                class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                            />

                            <span class="whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                @{{ limit.unit }}
                            </span>
                        </div>
                    </div>

                    <p
                        class="mt-1 text-xs italic text-red-600"
                        v-if="limit.error"
                        v-text="limit.error"
                    >
                    </p>
                </x-admin::form.control-group>

                <!-- IP Allowlist -->
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>
                        @lang('bagistoapi::app.integration.fields.ip-allowlist')
                    </x-admin::form.control-group.label>

                    <div class="flex flex-col gap-2">
                        <label class="flex w-max cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input
                                type="radio"
                                name="ip_mode"
                                value="any"
                                class="peer sr-only"
                                v-model="ipMode"
                                :disabled="disabled"
                            />

                            <span class="icon-radio-normal text-2xl peer-checked:icon-radio-selected peer-checked:text-blue-600"></span>

                            @lang('bagistoapi::app.integration.fields.ip-any')
                        </label>

                        <label class="flex w-max cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input
                                type="radio"
                                name="ip_mode"
                                value="restricted"
                                class="peer sr-only"
                                v-model="ipMode"
                                :disabled="disabled"
                            />

                            <span class="icon-radio-normal text-2xl peer-checked:icon-radio-selected peer-checked:text-blue-600"></span>

                            @lang('bagistoapi::app.integration.fields.ip-restricted')
                        </label>

                        <div v-show="ipMode === 'restricted'">
                            <textarea
                                name="allowed_ips_text"
                                rows="4"
                                v-model="allowedIpsText"
                                :disabled="disabled"
                                class="w-full rounded-md border px-3 py-2.5 font-mono text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                placeholder="10.0.0.0/24&#10;2001:db8::/32"
                            ></textarea>

                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                @lang('bagistoapi::app.integration.fields.ip-list-hint')
                            </p>
                        </div>
                    </div>

                    @error('allowed_ips')
                        <p class="mt-1 text-xs italic text-red-600">{{ $message }}</p>
                    @enderror
                </x-admin::form.control-group>
            </div>
        </script>

        <script type="module">
            app.component('v-integration-token-settings', {
                template: '#v-integration-token-settings-template',

                data() {
                    return {
                        disabled: @json($tokenIsHistoric),

                        expiresMode: @json($expiresMode),

                        expiresAt: @json(old('expires_at', $expiresAt?->format('Y-m-d'))),

                        ipMode: @json($ipMode),

                        allowedIpsText: @json($allowedIpsText),

                        limits: [
                            {
                                name: 'rate_limit_per_minute',
                                modeName: 'rate_min_mode',
                                mode: @json($rateMinMode),
                                value: @json(old('rate_limit_per_minute', $rateMin)),
                                label: @json(trans('bagistoapi::app.integration.fields.rate-limit-per-minute')),
                                unit: @json(trans('bagistoapi::app.integration.fields.requests-per-minute')),
                                error: @json($errors->first('rate_limit_per_minute') ?: null),
                            }, {
                                name: 'rate_limit_per_day',
                                modeName: 'rate_day_mode',
                                mode: @json($rateDayMode),
                                value: @json(old('rate_limit_per_day', $rateDay)),
                                label: @json(trans('bagistoapi::app.integration.fields.rate-limit-per-day')),
                                unit: @json(trans('bagistoapi::app.integration.fields.requests-per-day')),
                                error: @json($errors->first('rate_limit_per_day') ?: null),
                            },
                        ],
                    };
                },
            });
        </script>
    @endPushOnce
@endif

@if ($isEdit && $plainToken)
    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-integration-token-copy-template"
        >
            <button
                type="button"
                class="grid h-9 w-9 flex-shrink-0 place-items-center rounded-md text-gray-500 transition-all hover:bg-gray-200 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                title="@lang('bagistoapi::app.integration.edit.copy-btn')"
                @click="copy"
            >
                <span class="icon-copy text-2xl"></span>
            </button>
        </script>

        <script type="module">
            app.component('v-integration-token-copy', {
                template: '#v-integration-token-copy-template',

                props: ['value'],

                methods: {
                    copy() {
                        navigator.clipboard.writeText(this.value)
                            .then(() => this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: @json(trans('bagistoapi::app.integration.edit.token-copied')),
                            }));
                    },
                },
            });
        </script>
    @endPushOnce
@endif

@if ($isEdit && ! $tokenIsHistoric)
    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-integration-token-actions-template"
        >
            <div class="mt-4 flex flex-wrap items-center gap-2.5">
                @if ($tokenIsDraft && bouncer()->hasPermission('integration.generate'))
                    <button
                        type="button"
                        class="primary-button"
                        @click="generate"
                    >
                        @lang('bagistoapi::app.integration.edit.generate-btn')
                    </button>
                @endif

                @if ($tokenIsActive && bouncer()->hasPermission('integration.regenerate'))
                    <button
                        type="button"
                        class="secondary-button"
                        @click="regenerate"
                    >
                        @lang('bagistoapi::app.integration.edit.regenerate-btn')
                    </button>
                @endif

                @if ($tokenIsActive && bouncer()->hasPermission('integration.delete'))
                    <button
                        type="button"
                        class="transparent-button text-red-600 hover:bg-gray-200 dark:text-red-500 dark:hover:bg-gray-800"
                        @click="revoke"
                    >
                        @lang('bagistoapi::app.integration.edit.revoke-btn')
                    </button>
                @endif
            </div>
        </script>

        <script type="module">
            app.component('v-integration-token-actions', {
                template: '#v-integration-token-actions-template',

                methods: {
                    generate() {
                        this.$emitter.emit('open-confirm-modal', {
                            title: @json(trans('bagistoapi::app.integration.confirm.generate.title')),
                            message: @json(trans('bagistoapi::app.integration.confirm.generate.message')),

                            agree: () => {
                                const settings = new FormData(this.$el.closest('form'));

                                settings.delete('_method');

                                this.submit(@json(route('admin.integration.generate', $token->id)), settings);
                            },
                        });
                    },

                    regenerate() {
                        this.$emitter.emit('open-confirm-modal', {
                            title: @json(trans('bagistoapi::app.integration.confirm.regenerate.title')),
                            message: @json(trans('bagistoapi::app.integration.confirm.regenerate.message')),

                            agree: () => this.submit(@json(route('admin.integration.regenerate', $token->id))),
                        });
                    },

                    revoke() {
                        this.$emitter.emit('open-confirm-modal', {
                            title: @json(trans('bagistoapi::app.integration.confirm.revoke.title')),
                            message: @json(trans('bagistoapi::app.integration.confirm.revoke.message')),

                            agree: () => {
                                this.$axios.delete(@json(route('admin.integration.destroy', $token->id)))
                                    .then(response => {
                                        this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                                        window.location.reload();
                                    })
                                    .catch(error => {
                                        this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                                    });
                            },
                        });
                    },

                    submit(action, data = new FormData()) {
                        const form = document.createElement('form');

                        form.method = 'POST';

                        form.action = action;

                        data.set('_token', @json(csrf_token()));

                        data.forEach((value, name) => {
                            const input = document.createElement('input');

                            input.type = 'hidden';

                            input.name = name;

                            input.value = value;

                            form.appendChild(input);
                        });

                        document.body.appendChild(form);

                        form.submit();
                    },
                },
            });
        </script>
    @endPushOnce
@endif

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-integration-access-control-template"
    >
        <div>
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('bagistoapi::app.integration.fields.permission-type')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="permission_type"
                    id="permission_type"
                    rules="required"
                    v-model="permission_type"
                    :label="trans('bagistoapi::app.integration.fields.permission-type')"
                >
                    <option value="all">@lang('bagistoapi::app.integration.permission_type.all')</option>
                    <option value="custom">@lang('bagistoapi::app.integration.permission_type.custom')</option>
                    <option value="same_as_web">@lang('bagistoapi::app.integration.permission_type.same_as_web')</option>
                </x-admin::form.control-group.control>

                <x-admin::form.control-group.error control-name="permission_type" />
            </x-admin::form.control-group>

            <p v-if="permission_type === 'same_as_web'" class="mb-3 text-xs text-gray-500">
                @lang('bagistoapi::app.integration.fields.same-as-web-hint')
            </p>

            <div v-if="permission_type === 'custom'">
                <x-admin::form.control-group.error control-name="permissions" />

                <x-admin::tree.view
                    input-type="checkbox"
                    value-field="key"
                    id-field="key"
                    :items="json_encode(acl()->getItems())"
                    :value="json_encode($abilities)"
                    :fallback-locale="config('app.fallback_locale')"
                />
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-integration-access-control', {
            template: '#v-integration-access-control-template',

            data() {
                return {
                    permission_type: @json($permissionType),
                };
            },
        });
    </script>
@endPushOnce
