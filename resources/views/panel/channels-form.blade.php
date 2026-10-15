@extends('template.dash')

@section('title', $channel->exists ? __('Edit channel') : __('Create channel'))

@section('header', $channel->exists ? $channel->title : __('Create channel'))

@section('page')
    <div class="mx-auto max-w-5xl space-y-6">
        @include('template.msg')

        <form method="post" action="{{ $channel->exists ? url('channels/'.$channel->id) : url('channels') }}" id="channel-form">
            @csrf
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="space-y-6">
                    <flux:card class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ __('Channel') }}</flux:heading>
                            <flux:text class="mt-1">{{ __('The bot must be an admin of the channel to post.') }}</flux:text>
                        </div>
                        <flux:input label="{{ __('Title') }}" name="title" value="{{ old('title', $channel->title) }}"/>
                        <flux:input label="{{ __('Chat id') }}" description="{{ __('Numeric id, usually starting with -100') }}" name="chat_id" value="{{ old('chat_id', $channel->chat_id) }}" dir="ltr"/>
                        <flux:input label="{{ __('Username') }}" description="{{ __('Shown in posts through {channel_username}') }}" name="username" value="{{ old('username', $channel->username) }}" dir="ltr"/>
                        <flux:radio.group label="{{ __('Language') }}" name="language" variant="segmented" size="sm">
                            <flux:radio label="{{ __('language.fa') }}" value="fa" :checked="old('language', $channel->language) === 'fa'"/>
                            <flux:radio label="{{ __('language.en') }}" value="en" :checked="old('language', $channel->language) === 'en'"/>
                        </flux:radio.group>
                    </flux:card>

                    <flux:card class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ __('Post template') }}</flux:heading>
                            <flux:text class="mt-1">{{ __('Telegram Markdown. Placeholders:') }} <code dir="ltr">{title} {description} {short_url} {original_url} {feed_url} {channel_username}</code></flux:text>
                        </div>
                        <flux:textarea label="{{ __('Message text') }}" name="template_text" rows="8">{{ old('template_text', $channel->template_text) }}</flux:textarea>
                        <flux:textarea label="{{ __('Buttons') }}" description="{{ __('One per line as label | url. A button whose url is empty for a post is left out.') }}" name="template_buttons" rows="4" dir="auto">{{ old('template_buttons', $channel->buttonsAsText()) }}</flux:textarea>
                    </flux:card>
                </div>

                <div class="space-y-6">
                    <flux:card>
                        <flux:heading size="lg">{{ __('Preview') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Rendered with sample data as you type.') }}</flux:text>
                        <div class="mt-4 rounded-xl bg-zinc-100 p-4 dark:bg-zinc-900">
                            <pre id="preview-text" class="whitespace-pre-wrap font-sans text-sm leading-6" dir="auto"></pre>
                            <div id="preview-buttons" class="mt-3 flex flex-col gap-2"></div>
                        </div>
                    </flux:card>

                    <div class="flex justify-end gap-2">
                        <flux:button href="{{ url('channels') }}" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary" size="sm">{{ __('Save') }}</flux:button>
                    </div>
                </div>
            </div>
        </form>

        @if($channel->exists)
            <flux:card>
                <flux:heading size="lg">{{ __('Delete channel') }}</flux:heading>
                <flux:text class="mt-1">{{ __('The channel is detached from every project. Nothing is deleted on Telegram.') }}</flux:text>
                <form method="post" action="{{ url('channels/'.$channel->id.'/delete') }}" class="mt-4" onsubmit="return confirm(@json(__('Delete this channel?')))">
                    @csrf
                    <flux:button type="submit" variant="danger" size="sm" icon="trash">{{ __('Delete') }}</flux:button>
                </form>
            </flux:card>
        @endif
    </div>
@endsection

@push('js')
    <script>
        (function () {
            const form = document.getElementById('channel-form');
            const text = document.getElementById('preview-text');
            const buttons = document.getElementById('preview-buttons');
            let timer = null;

            function render() {
                const data = new FormData(form);
                fetch(@json(url('channels/preview')), {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: data
                }).then(r => r.json()).then(post => {
                    text.textContent = post.text;
                    buttons.innerHTML = '';
                    post.buttons.forEach(b => {
                        const el = document.createElement('div');
                        el.className = 'rounded-lg bg-white px-3 py-2 text-center text-sm text-blue-600 shadow-sm dark:bg-zinc-800 dark:text-blue-400';
                        el.textContent = b.label;
                        el.title = b.url;
                        buttons.appendChild(el);
                    });
                }).catch(() => {});
            }

            form.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(render, 300); });
            render();
        })();
    </script>
@endpush
