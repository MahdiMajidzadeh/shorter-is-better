<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BotChat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Admin page listing every chat that ever talked to the bot. Approving a
 * chat means binding it to a user and setting it active.
 */
class BotChatController extends Controller
{
    public function index(): View
    {
        return view('panel.bot-chats', [
            'chats' => BotChat::with('user', 'project')->orderByRaw("case status when 'pending' then 0 when 'active' then 1 else 2 end")->orderByDesc('last_seen_at')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, BotChat $chat): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', Rule::exists('users', 'id')],
            'status'  => ['required', Rule::in(BotChat::STATUSES)],
        ]);

        if ($data['status'] === BotChat::ACTIVE && empty($data['user_id'])) {
            return redirect('bot/chats')->with('msg-error', __('Pick a user before approving a chat.'));
        }

        $userId = $data['user_id'] ?: null;

        $chat->update([
            'user_id' => $userId,
            'status'  => $data['status'],
            // A new owner starts from their own first project.
            'project_id' => $userId === $chat->user_id ? $chat->project_id : null,
        ]);

        return redirect('bot/chats')->with('msg-ok', __('Chat updated.'));
    }

    public function destroy(BotChat $chat): RedirectResponse
    {
        $chat->delete();

        return redirect('bot/chats')->with('msg-ok', __('Chat removed. It is recreated as pending if it writes again.'));
    }
}
