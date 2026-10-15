<?php

namespace App\Jobs;

use App\Models\Link;
use App\Services\PageMeta;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Fills a link's title after creation so the shortening path never waits on
 * the destination site.
 */
class FetchLinkTitle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public Link $link) {}

    public function handle(PageMeta $meta): void
    {
        if ($this->link->title) {
            return;
        }

        $title = $meta->fetch($this->link->destination_url)['title'];

        $this->link->forceFill([
            'title' => mb_substr($title ?: '', 0, 500) ?: null,
        ])->save();
    }
}
