<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A Telegram channel the bot can post to. The post template is per channel:
 * free text with placeholders, and a list of inline buttons.
 */
class Channel extends Model
{
    public const LANGUAGES = ['fa', 'en'];

    public const PLACEHOLDERS = ['title', 'description', 'short_url', 'original_url', 'feed_url', 'channel_username'];

    protected $fillable = ['title', 'chat_id', 'username', 'language', 'template_text', 'template_buttons'];

    protected $casts = [
        'template_buttons' => 'array',
    ];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withPivot('is_default');
    }

    public function isRtl(): bool
    {
        return $this->language === 'fa';
    }

    /** The post the bot produced before templates existed. */
    public static function legacyTemplateText(): string
    {
        return "*{title}*\n\n{description}\n\n`{short_url}`\n{channel_username}";
    }

    public static function legacyTemplateButtons(): array
    {
        return [
            ['label' => 'Read Article', 'url' => '{short_url}'],
            ['label' => 'Read on PM Feed', 'url' => '{feed_url}'],
        ];
    }

    public static function defaultTemplateText(string $language): string
    {
        return $language === 'fa'
            ? "*{title}*\n\n{description}\n\n`{short_url}`\n{channel_username}"
            : static::legacyTemplateText();
    }

    public static function defaultTemplateButtons(string $language): array
    {
        return $language === 'fa'
            ? [
                ['label' => 'مطالعه مطلب', 'url' => '{short_url}'],
                ['label' => 'مشاهده در فید', 'url' => '{feed_url}'],
            ]
            : static::legacyTemplateButtons();
    }

    /**
     * Buttons are edited as plain lines of "label | url". Blank lines and
     * lines without a separator are ignored.
     *
     * @return list<array{label: string, url: string}>
     */
    public static function parseButtons(string $text): array
    {
        $buttons = [];

        foreach (preg_split('/\r?\n/', $text) as $line) {
            if (! str_contains($line, '|')) {
                continue;
            }

            [$label, $url] = array_map('trim', explode('|', $line, 2));

            if ($label !== '' && $url !== '') {
                $buttons[] = ['label' => $label, 'url' => $url];
            }
        }

        return $buttons;
    }

    public function buttonsAsText(): string
    {
        return collect($this->template_buttons ?? [])
            ->map(fn (array $b) => ($b['label'] ?? '').' | '.($b['url'] ?? ''))
            ->implode("\n");
    }

    public function setUsernameAttribute(?string $value): void
    {
        $value = trim((string) $value);

        if ($value !== '' && ! str_starts_with($value, '@')) {
            $value = '@'.$value;
        }

        $this->attributes['username'] = $value === '' ? null : $value;
    }
}
