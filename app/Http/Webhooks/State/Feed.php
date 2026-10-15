<?php

namespace App\Http\Webhooks\State;

/**
 * Like ForChannel, but asks for a second "feed" url. The channel template
 * decides how it shows up, usually as a second button under the article.
 */
class Feed extends ForChannel
{
    protected string $actionPrefix = 'feed_';

    public function handleStep2()
    {
        if (! $this->receiveUrl()) {
            return;
        }

        $this->chat->message('send the feed url')->send();
        $this->nextStep();
    }

    public function handleStep3()
    {
        if (! $this->isUrl($this->message)) {
            $this->chat->message('feed url is not valid')->send();

            return;
        }

        $this->chat->storage()->set('data.feed_url', $this->message);
        $this->chat->storage()->set('data.feed_short', $this->makeShort($this->message));

        $this->sendPreview();
        $this->nextStep();
    }

    public function handleStep4()
    {
        $this->handleKeyboard();
    }

    public function handleStep5()
    {
        $this->publish($this->message);
        $this->done();
    }
}
