<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\Request;
use App\Services\PostComposer;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ChannelController extends Controller
{
    public function index(): View
    {
        return view('panel.channels-all', [
            'channels' => Channel::with('projects')->orderBy('id')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $language = in_array($request->get('language'), Channel::LANGUAGES, true) ? $request->get('language') : 'fa';

        return view('panel.channels-form', [
            'channel' => new Channel([
                'language'         => $language,
                'template_text'    => Channel::defaultTemplateText($language),
                'template_buttons' => Channel::defaultTemplateButtons($language),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Channel::create($this->validated($request));

        return redirect('channels')->with('msg-ok', __('Channel created.'));
    }

    public function edit(Channel $channel): View
    {
        return view('panel.channels-form', ['channel' => $channel]);
    }

    public function update(Request $request, Channel $channel): RedirectResponse
    {
        $channel->update($this->validated($request, $channel));

        return redirect('channels')->with('msg-ok', __('Channel saved.'));
    }

    public function destroy(Channel $channel): RedirectResponse
    {
        $channel->delete();

        return redirect('channels')->with('msg-ok', __('Channel deleted.'));
    }

    /** Render the template with sample data, for the editor's preview. */
    public function preview(Request $request, PostComposer $composer): array
    {
        $channel = new Channel([
            'username'         => $request->get('username'),
            'language'         => $request->get('language', 'fa'),
            'template_text'    => (string) $request->get('template_text'),
            'template_buttons' => Channel::parseButtons((string) $request->get('template_buttons')),
        ]);

        return $composer->compose($channel, [
            'title'        => __('Sample article title'),
            'description'  => __('A short description of the article, as read from the page.'),
            'short_url'    => 'https://example.com/s/abc12',
            'original_url' => 'https://example.com/articles/sample',
            'feed_url'     => 'https://example.com/s/feed1',
        ]);
    }

    private function validated(Request $request, ?Channel $channel = null): array
    {
        $data = $request->validate([
            'title'            => 'required|string|max:100',
            'chat_id'          => ['required', 'string', 'max:64', Rule::unique('channels', 'chat_id')->ignore($channel?->id)],
            'username'         => 'nullable|string|max:64',
            'language'         => ['required', Rule::in(Channel::LANGUAGES)],
            'template_text'    => 'required|string|max:4000',
            'template_buttons' => 'nullable|string|max:2000',
        ]);

        $data['template_buttons'] = Channel::parseButtons($data['template_buttons'] ?? '');

        return $data;
    }
}
