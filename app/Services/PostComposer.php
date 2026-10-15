<?php

namespace App\Services;

use App\Models\Channel;

/**
 * Renders a channel's post template for one link. Placeholders look like
 * {title}; unknown ones are left as typed. Buttons whose url resolves to
 * nothing are dropped, which is how one template serves both the plain
 * article post and the article-plus-feed post.
 */
class PostComposer
{
    private const RLM = "\u{200F}";

    /**
     * @param  array<string, string|null>  $data  values for Channel::PLACEHOLDERS
     * @return array{text: string, buttons: list<array{label: string, url: string}>}
     */
    public function compose(Channel $channel, array $data): array
    {
        $data += ['channel_username' => $channel->username];

        $text = $this->render($channel->template_text, $data);

        if ($channel->isRtl()) {
            $text = $this->markRtl($text);
        }

        $buttons = [];

        foreach ($channel->template_buttons ?? [] as $button) {
            $url = trim($this->render($button['url'] ?? '', $data));
            $label = trim($this->render($button['label'] ?? '', $data));

            if ($url === '' || $label === '') {
                continue;
            }

            $buttons[] = ['label' => $label, 'url' => $url];
        }

        return ['text' => $text, 'buttons' => $buttons];
    }

    public function render(string $template, array $data): string
    {
        $replacements = [];

        foreach (Channel::PLACEHOLDERS as $placeholder) {
            $replacements['{'.$placeholder.'}'] = (string) ($data[$placeholder] ?? '');
        }

        return strtr($template, $replacements);
    }

    /**
     * Prefix each non-empty line with a right-to-left mark so lines that
     * start with a latin word or a url still render right-aligned.
     */
    private function markRtl(string $text): string
    {
        $lines = explode("\n", $text);

        foreach ($lines as &$line) {
            if (trim($line) !== '' && ! str_starts_with($line, self::RLM)) {
                $line = self::RLM.$line;
            }
        }

        return implode("\n", $lines);
    }
}
