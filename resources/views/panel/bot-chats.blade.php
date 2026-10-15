@extends('template.dash')

@section('title', __('Bot access'))

@section('header', __('Bot access'))

@section('page')
    @include('template.msg')

    <flux:text class="mb-4">{{ __('Every chat that ever wrote to the bot. A chat can use the bot once it is bound to a user and approved.') }}</flux:text>

    <flux:card class="!p-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Telegram') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Panel user') }}</flux:table.column>
                <flux:table.column>{{ __('Active project') }}</flux:table.column>
                <flux:table.column>{{ __('Last seen') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($chats as $chat)
                    <flux:table.row :key="$chat->id">
                        <flux:table.cell>
                            <div class="font-medium">{{ $chat->displayName() }}</div>
                            <div class="text-xs text-zinc-500" dir="ltr">{{ $chat->username ? '@'.$chat->username.' · ' : '' }}{{ $chat->chat_id }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="match($chat->status) { 'active' => 'green', 'blocked' => 'red', default => 'amber' }">{{ __('status.'.$chat->status) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell colspan="3">
                            <form method="post" action="{{ url('bot/chats/'.$chat->id) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <flux:select name="user_id" size="sm" class="!w-48">
                                    <flux:select.option value="">{{ __('No user') }}</flux:select.option>
                                    @foreach($users as $user)
                                        <flux:select.option value="{{ $user->id }}" :selected="$chat->user_id === $user->id">{{ $user->name }} ({{ $user->username }})</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select name="status" size="sm" class="!w-36">
                                    @foreach(\App\Models\BotChat::STATUSES as $status)
                                        <flux:select.option value="{{ $status }}" :selected="$chat->status === $status">{{ __('status.'.$status) }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:button type="submit" size="sm" variant="primary">{{ __('Save') }}</flux:button>
                                <flux:text size="sm" class="ms-2">{{ $chat->project?->name }}</flux:text>
                                <flux:text size="sm" class="ms-auto whitespace-nowrap">{{ local_date($chat->last_seen_at, true) }}</flux:text>
                            </form>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <form method="post" action="{{ url('bot/chats/'.$chat->id.'/delete') }}" onsubmit="return confirm(@json(__('Remove this chat?')))">
                                @csrf
                                <flux:button type="submit" size="sm" variant="ghost" icon="trash" aria-label="{{ __('Remove') }}"/>
                            </form>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">{{ __('No chat has written to the bot yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
@endsection
