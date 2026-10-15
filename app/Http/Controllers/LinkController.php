<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Visit;
use App\Services\Shortener;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Exceptions\ShortenerException;
use Illuminate\Database\Eloquent\Collection;

class LinkController extends Controller
{
    public function all(Request $request): View
    {
        $data['links'] = Link::forProject($this->project())
            ->withCount(['visits as human_visits_count' => fn ($q) => $q->human()])
            ->orderBy('id', 'desc')
            ->paginate(40);

        return view('panel.links-all', $data);
    }

    public function create(Request $request): View
    {
        $this->project();

        return view('panel.links-create');
    }

    public function createSubmit(Request $request, Shortener $shortener): RedirectResponse
    {
        $request->validate([
            'url' => 'required|url',
            'key' => 'nullable|string|max:64',
        ]);

        try {
            $link = $shortener->make(
                $this->project(),
                $request->user(),
                $request->get('url'),
                Link::SOURCE_PANEL,
                $request->filled('key') ? $request->get('key') : null,
            );
        } catch (ShortenerException $e) {
            return redirect()->back()->withInput()->with('msg-error', $e->getMessage());
        }

        return redirect('links/'.$link->url_key);
    }

    public function detail(Request $request, string $short): View
    {
        $link = Link::findByKey($short);

        abort_if(! $link || ! $request->user()->canAccessProject($link->project_id), 404);

        $data['short'] = $link;
        $data['operating_system'] = $this->getVisitData($link->id, 'operating_system');
        $data['device_type'] = $this->getVisitData($link->id, 'device_type');
        $data['browser'] = $this->getVisitData($link->id, 'browser');
        $data['views'] = Visit::query()
            ->select(DB::raw('Date(visited_at) as x'), DB::raw('count(*) as y'))
            ->where('short_url_id', $link->id)
            ->human()
            ->where('visited_at', '>', now()->subDays(30))
            ->groupBy(DB::raw('Date(visited_at)'))
            ->orderBy('x')
            ->get();

        return view('panel.links-detail', $data);
    }

    public function deleteSubmit(Request $request, int $id): RedirectResponse
    {
        $link = Link::findOrFail($id);

        abort_unless($request->user()->canAccessProject($link->project_id), 404);

        $link->visits()->delete();
        $link->delete();

        return redirect('links')->with('msg-ok', __('Link deleted.'));
    }

    public function bulk(Request $request): View
    {
        $this->project();

        return view('panel.links-bulk');
    }

    public function bulkSubmit(Request $request, Shortener $shortener): RedirectResponse
    {
        $request->validate([
            'text' => 'required|string',
        ]);

        $result = $shortener->bulk($this->project(), $request->user(), $request->get('text'), Link::SOURCE_PANEL);

        return redirect('links/bulk')
            ->with('converted_text', $result['text'])
            ->with('converted_count', $result['links']->count());
    }

    public function logs(Request $request): View
    {
        $data['logs'] = Visit::forProject($this->project())
            ->with('link')
            ->orderBy('id', 'desc')
            ->paginate(40);

        return view('panel.links-logs', $data);
    }

    private function getVisitData(int $id, string $type): Collection
    {
        return Visit::query()
            ->where('short_url_id', $id)
            ->human()
            ->groupBy($type)
            ->selectRaw($type.' as name, count(*) as total')
            ->orderBy('total', 'desc')
            ->get();
    }
}
