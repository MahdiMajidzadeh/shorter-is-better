<?php

use Morilog\Jalali\Jalalian;
use Illuminate\Support\Carbon;
use DefStudio\Telegraph\Models\TelegraphBot;

if (! function_exists('chart_data_line')) {
    function chart_data_line($data, $label, $col, $chartLabel)
    {
        return [
            'labels'   => $data->pluck($label),
            'datasets' => [[
                'label' => $chartLabel,
                'data'  => $data->pluck($col),
            ]],
        ];
    }
}

if (! function_exists('bot_commands')) {
    function bot_commands(): array
    {
        return [
            'short'       => 'short url',
            'short_key'   => 'short url with custom key',
            'for_channel' => 'make short for channel',
            'feed'        => 'channel post with article and feed links',
            'report'      => 'short report of last 7 days',
            'stat'        => 'show stat for shorted url',
            'bulk'        => 'short bulk of links in text',
        ];
    }
}

if (! function_exists('bot_update')) {
    function bot_update()
    {
        $bots = TelegraphBot::all();

        foreach ($bots as $bot) {
            $bot->unregisterCommands()->send();
            $v = $bot->registerCommands(bot_commands())->send();
        }
    }
}

if (! function_exists('local_date')) {
    /**
     * A date for humans in the current locale: Jalali for Persian, ISO-ish
     * otherwise. Pass `true` as the second argument to include the time.
     */
    function local_date(mixed $value, bool $withTime = false): string
    {
        if (is_null($value)) {
            return '';
        }

        $carbon = $value instanceof Carbon ? $value : Carbon::parse($value);

        if (app()->getLocale() === 'fa') {
            return Jalalian::fromCarbon($carbon)->format($withTime ? 'Y/m/d H:i' : 'Y/m/d');
        }

        return $carbon->format($withTime ? 'Y-m-d H:i' : 'Y-m-d');
    }
}

if (! function_exists('local_number')) {
    /** Thousands-separated number, with Persian digits in the Persian locale. */
    function local_number(int|float $value): string
    {
        $formatted = number_format($value);

        if (app()->getLocale() === 'fa') {
            return strtr($formatted, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹', ',' => '٬']);
        }

        return $formatted;
    }
}
