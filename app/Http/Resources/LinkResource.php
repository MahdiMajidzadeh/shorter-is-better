<?php

namespace App\Http\Resources;

use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Link */
class LinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'key'             => $this->url_key,
            'short_url'       => $this->default_short_url,
            'destination_url' => $this->destination_url,
            'title'           => $this->title,
            'source'          => $this->source,
            'clicks'          => $this->when(isset($this->human_visits_count), fn () => (int) $this->human_visits_count),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
