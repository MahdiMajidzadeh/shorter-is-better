<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\BotChat;
use App\Models\Channel;
use App\Services\PageMeta;
use App\Services\Shortener;
use Illuminate\Http\Request;
use SergiX44\Nutgram\Nutgram;
use App\Services\PostComposer;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use App\Services\ChannelPublisher;
use Illuminate\Contracts\View\View;
use App\Http\Resources\LinkResource;

/**
 * The Telegram mini app: one Blade shell plus JSON endpoints under
 * /app/api, authenticated by AuthenticateMiniApp. Everything a user can do
 * in the bot, with a screen instead of a conversation.
 */
class MiniAppController extends Controller
{
    public function shell(): View
    {
        return view('miniapp.index');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $project = $this->project();
        $chat = app(BotChat::class);

        return response()->json([
            'user'     => ['id' => $user->id, 'name' => $user->name, 'locale' => app()->getLocale(), 'is_admin' => $user->is_admin],
            'project'  => $this->projectPayload($project),
            'projects' => $user->accessibleProjectList()->map(fn ($p) => $this->projectPayload($p))->values(),
            'channels' => $project->channels()->orderByDesc('channel_project.is_default')->orderBy('channels.id')->get()
                ->map(fn (Channel $c) => ['id' => $c->id, 'title' => $c->title, 'language' => $c->language, 'is_default' => (bool) $c->pivot->is_default])->values(),
            'chat'    => ['id' => $chat->chat_id],
            'strings' => __('miniapp'),
        ]);
    }

    public function switchProject(Request $request): JsonResponse
    {
        $data = $request->validate(['project_id' => 'required|integer']);
        $project = $request->user()->accessibleProjectList()->firstWhere('id', (int) $data['project_id']);

        abort_unless($project, 403, __('bot_replies.project_not_allowed'));

        app(BotChat::class)->forceFill(['project_id' => $project->id])->save();

        return response()->json(['project' => $this->projectPayload($project)]);
    }

    public function links(Request $request): JsonResponse
    {
        $request->validate(['search' => 'nullable|string|max:200', 'page' => 'nullable|integer|min:1']);

        $links = Link::forProject($this->project())
            ->withCount(['visits as human_visits_count' => fn ($q) => $q->human()])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('destination_url', 'like', '%'.$request->get('search').'%')
                ->orWhere('title', 'like', '%'.$request->get('search').'%')
                ->orWhere('url_key', $request->get('search'))))
            ->orderByDesc('id')
            ->paginate(20);

        return LinkResource::collection($links)->response();
    }

    public function createLink(Request $request, Shortener $shortener): JsonResponse
    {
        $data = $request->validate(['url' => 'required|string|max:2048', 'key' => 'nullable|string|max:64']);

        $link = $shortener->make($this->project(), $request->user(), $data['url'], Link::SOURCE_MINIAPP, $data['key'] ?? null);

        return (new LinkResource($link->refresh()))->response()->setStatusCode(201);
    }

    public function bulk(Request $request, Shortener $shortener): JsonResponse
    {
        $data = $request->validate(['text' => 'required|string|max:20000']);

        $result = $shortener->bulk($this->project(), $request->user(), $data['text'], Link::SOURCE_MINIAPP);

        return response()->json(['text' => $result['text'], 'count' => $result['links']->count()]);
    }

    public function stats(ReportService $reports, string $key): JsonResponse
    {
        $link = Link::forProject($this->project())
            ->withCount(['visits as human_visits_count' => fn ($q) => $q->human()])
            ->where('url_key', $key)
            ->firstOrFail();

        return response()->json(['link' => new LinkResource($link), 'stats' => $this->localised($reports->linkStats($link, 30))]);
    }

    public function report(Request $request, ReportService $reports): JsonResponse
    {
        $request->validate(['days' => 'nullable|integer|min:1|max:90']);

        return response()->json($this->localised($reports->summary($this->project(), (int) $request->get('days', 7))));
    }

    /** Shorten the article (and feed) url and render the channel template, without posting. */
    public function previewPost(Request $request, Shortener $shortener, PageMeta $meta, PostComposer $composer): JsonResponse
    {
        $data = $request->validate([
            'channel_id' => 'required|integer',
            'url'        => 'required|string|max:2048',
            'feed_url'   => 'nullable|string|max:2048',
        ]);

        $channel = $this->channel((int) $data['channel_id']);
        $project = $this->project();

        $article = $shortener->make($project, $request->user(), $data['url'], Link::SOURCE_MINIAPP, null, $channel);
        $feed = ! empty($data['feed_url']) ? $shortener->make($project, $request->user(), $data['feed_url'], Link::SOURCE_MINIAPP, null, $channel) : null;
        $page = $meta->fetch($data['url']);

        $post = $composer->compose($channel, [
            'title'        => $page['title'],
            'description'  => $page['description'],
            'short_url'    => $article->default_short_url,
            'original_url' => $data['url'],
            'feed_url'     => $feed?->default_short_url,
        ]);

        return response()->json([
            'channel'   => ['id' => $channel->id, 'title' => $channel->title],
            'text'      => $post['text'],
            'buttons'   => $post['buttons'],
            'image'     => $page['image'] ?: null,
            'short_url' => $article->default_short_url,
            'feed_url'  => $feed?->default_short_url,
        ]);
    }

    public function publishPost(Request $request, Nutgram $bot, PostComposer $composer, ChannelPublisher $publisher): JsonResponse
    {
        $data = $request->validate([
            'channel_id' => 'required|integer',
            'text'       => 'required|string|max:4000',
            'short_url'  => 'required|string|max:500',
            'feed_url'   => 'nullable|string|max:500',
            'image'      => 'nullable|string|max:2000',
        ]);

        $channel = $this->channel((int) $data['channel_id']);

        // Only this project's own short links may be attached to a post.
        foreach (array_filter([$data['short_url'], $data['feed_url'] ?? null]) as $url) {
            abort_unless(Link::forProject($this->project())->where('default_short_url', $url)->exists(), 422, __('The url is not valid.'));
        }

        // Buttons always come from the template, never from the client.
        $buttons = $composer->compose($channel, [
            'short_url' => $data['short_url'],
            'feed_url'  => $data['feed_url'] ?? '',
        ])['buttons'];

        $sent = $publisher->publish($bot, $channel, $data['text'], $buttons, $data['image'] ?? null);

        abort_if($sent === null, 502, __('bot_replies.publish_failed', ['channel' => $channel->title]));

        return response()->json(['ok' => true, 'message_id' => $sent->message_id]);
    }

    private function channel(int $id): Channel
    {
        $channel = $this->project()->channels()->where('channels.id', $id)->first();

        abort_unless($channel, 404, __('bot_replies.no_channel'));

        return $channel;
    }

    private function projectPayload($project): array
    {
        return ['id' => $project->id, 'name' => $project->name, 'domain' => $project->domain];
    }

    /** Add display-ready labels (Jalali dates, Persian digits) next to the raw values. */
    private function localised(array $stats): array
    {
        if (isset($stats['daily'])) {
            $stats['daily'] = array_map(fn ($row) => $row + ['label' => local_date($row['date'])], $stats['daily']);
        }

        return $stats;
    }
}
