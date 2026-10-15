<?php

namespace App\Http\Controllers\Api;

use App\Models\Link;
use App\Services\Shortener;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\LinkResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LinkController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'search'   => 'nullable|string|max:200',
        ]);

        $links = Link::forProject($this->project())
            ->withCount(['visits as human_visits_count' => fn ($q) => $q->human()])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('destination_url', 'like', '%'.$request->get('search').'%')
                ->orWhere('title', 'like', '%'.$request->get('search').'%')
                ->orWhere('url_key', $request->get('search'))))
            ->orderByDesc('id')
            ->paginate((int) $request->get('per_page', 25));

        return LinkResource::collection($links);
    }

    public function store(Request $request, Shortener $shortener): JsonResponse
    {
        $data = $request->validate([
            'url' => 'required|string|max:2048',
            'key' => 'nullable|string|max:64',
        ]);

        $link = $shortener->make($this->project(), $request->user(), $data['url'], Link::SOURCE_API, $data['key'] ?? null);

        // The title arrives from a queued job; on a sync queue it is already there.
        return (new LinkResource($link->refresh()))->response()->setStatusCode(201);
    }

    public function bulk(Request $request, Shortener $shortener): JsonResponse
    {
        $data = $request->validate([
            'text' => 'required|string|max:20000',
        ]);

        $result = $shortener->bulk($this->project(), $request->user(), $data['text'], Link::SOURCE_API);

        return response()->json([
            'text'  => $result['text'],
            'links' => LinkResource::collection($result['links']),
        ]);
    }

    public function show(string $key): LinkResource
    {
        return new LinkResource($this->find($key));
    }

    public function stats(Request $request, ReportService $reports, string $key): JsonResponse
    {
        $request->validate(['days' => 'nullable|integer|min:1|max:365']);

        $link = $this->find($key);

        return response()->json([
            'link'  => new LinkResource($link),
            'stats' => $reports->linkStats($link, (int) $request->get('days', 30)),
        ]);
    }

    public function destroy(string $key): Response
    {
        $link = $this->find($key);

        $link->visits()->delete();
        $link->delete();

        return response()->noContent();
    }

    private function find(string $key): Link
    {
        return Link::forProject($this->project())
            ->withCount(['visits as human_visits_count' => fn ($q) => $q->human()])
            ->where('url_key', $key)
            ->firstOrFail();
    }
}
