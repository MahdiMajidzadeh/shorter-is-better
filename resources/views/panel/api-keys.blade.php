@extends('template.dash')

@section('title', __('API keys'))

@section('header', __('API keys'))

@section('page')
    <div class="mx-auto max-w-4xl space-y-6">
        @include('template.msg')

        @if(session('plain_token'))
            <flux:card>
                <flux:heading size="lg">{{ __('Your new key') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Send it as a bearer token: Authorization: Bearer <key>. It is shown only once.') }}</flux:text>
                <flux:input value="{{ session('plain_token') }}" readonly copyable dir="ltr" class="mt-4"/>
            </flux:card>
        @endif

        <flux:card class="!p-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    @if(auth()->user()->is_admin)
                        <flux:table.column>{{ __('Owner') }}</flux:table.column>
                    @endif
                    <flux:table.column>{{ __('Scopes') }}</flux:table.column>
                    <flux:table.column>{{ __('Last used') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($keys as $key)
                        <flux:table.row :key="$key->id">
                            <flux:table.cell class="font-medium">{{ $key->name }}</flux:table.cell>
                            @if(auth()->user()->is_admin)
                                <flux:table.cell>{{ $key->tokenable?->name }}</flux:table.cell>
                            @endif
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($key->scopes() as $scope)
                                        <flux:badge size="sm" dir="ltr">{{ $scope }}</flux:badge>
                                    @endforeach
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $key->last_used_at ? local_date($key->last_used_at, true) : __('Never') }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <form method="post" action="{{ url('api-keys/'.$key->id.'/delete') }}" onsubmit="return confirm(@json(__('Revoke this key?')))">
                                    @csrf
                                    <flux:button type="submit" size="sm" variant="ghost" icon="trash">{{ __('Revoke') }}</flux:button>
                                </form>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">{{ __('No API keys for this project yet.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <flux:card>
            <form method="post" action="{{ url('api-keys') }}" class="space-y-6">
                @csrf
                <div>
                    <flux:heading size="lg">{{ __('Create key') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('The key works only on :project. Pick the scopes it needs.', ['project' => $currentProject->name]) }}</flux:text>
                </div>
                <flux:input label="{{ __('Name') }}" name="name" value="{{ old('name') }}" placeholder="{{ __('e.g. n8n, iOS shortcut') }}"/>
                <flux:checkbox.group label="{{ __('Scopes') }}">
                    @foreach($scopes as $scope => $label)
                        <flux:checkbox name="scopes[]" value="{{ $scope }}" label="{{ $scope }}" description="{{ __($label) }}" :checked="in_array($scope, old('scopes', ['links:create', 'links:read']))"/>
                    @endforeach
                </flux:checkbox.group>
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" size="sm">{{ __('Create key') }}</flux:button>
                </div>
            </form>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">{{ __('Endpoints') }}</flux:heading>
            <pre class="mt-3 overflow-x-auto text-xs leading-6" dir="ltr">GET    {{ url('api/v1/me') }}
GET    {{ url('api/v1/links') }}?search=&per_page=25
POST   {{ url('api/v1/links') }}            {"url": "https://…", "key": "optional"}
POST   {{ url('api/v1/links/bulk') }}       {"text": "…"}
GET    {{ url('api/v1/links/{key}') }}
GET    {{ url('api/v1/links/{key}/stats') }}?days=30
DELETE {{ url('api/v1/links/{key}') }}
GET    {{ url('api/v1/reports/summary') }}?days=7</pre>
        </flux:card>
    </div>
@endsection
