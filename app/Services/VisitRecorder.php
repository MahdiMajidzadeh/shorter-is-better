<?php

namespace App\Services;

use App\Models\Link;
use App\Models\Visit;
use WhichBrowser\Parser;
use Illuminate\Http\Request;

/**
 * Records a click the same way the previous shortener package did, so rows
 * written before and after the cutover classify identically.
 */
class VisitRecorder
{
    public function record(Link $link, Request $request): ?Visit
    {
        if (! $link->track_visits) {
            return null;
        }

        $agent = new Parser($request->userAgent());
        $isRobot = ($agent->device->type ?: null) === 'bot';

        return Visit::create([
            'short_url_id'             => $link->id,
            'ip_address'               => $link->track_ip_address ? $request->ip() : null,
            'operating_system'         => $link->track_operating_system && ! $isRobot ? ($agent->os->name ?? null) : null,
            'operating_system_version' => $link->track_operating_system_version && ! $isRobot ? ($agent->os->getVersion() ?: null) : null,
            'browser'                  => $link->track_browser ? ($agent->browser->getName() ?: null) : null,
            'browser_version'          => $link->track_browser_version ? ($agent->browser->getVersion() ?: null) : null,
            'referer_url'              => $link->track_referer_url ? $request->headers->get('referer') : null,
            'device_type'              => $link->track_device_type ? $this->deviceType($agent) : null,
            'visited_at'               => now(),
        ]);
    }

    private function deviceType(Parser $agent): ?string
    {
        return match ($agent->device->type ?: null) {
            'desktop' => Visit::DEVICE_DESKTOP,
            'mobile'  => Visit::DEVICE_MOBILE,
            'tablet'  => Visit::DEVICE_TABLET,
            'bot'     => Visit::DEVICE_ROBOT,
            default   => null,
        };
    }
}
