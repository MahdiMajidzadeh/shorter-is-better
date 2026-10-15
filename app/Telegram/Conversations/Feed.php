<?php

namespace App\Telegram\Conversations;

/**
 * Like ForChannel, but also asks for a feed url. The channel template
 * decides how it shows up, usually as a second button under the article.
 */
class Feed extends ForChannel
{
    protected bool $withFeed = true;
}
