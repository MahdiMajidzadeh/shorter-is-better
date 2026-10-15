<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Http\Request;
use App\Services\VisitRecorder;
use Illuminate\Http\RedirectResponse;

/**
 * Serves /s/{key} on every project domain. Keys are unique across projects,
 * so a link keeps resolving even if it is opened on another project's host.
 */
class RedirectController extends Controller
{
    public function __invoke(Request $request, VisitRecorder $recorder, string $key): RedirectResponse
    {
        $link = Link::where('url_key', $key)->firstOrFail();

        $recorder->record($link, $request);

        $destination = $link->destination_url;

        if ($link->forward_query_params && $request->getQueryString()) {
            $destination .= (str_contains($destination, '?') ? '&' : '?').$request->getQueryString();
        }

        return redirect($destination, $link->redirect_status_code ?: 301);
    }
}
