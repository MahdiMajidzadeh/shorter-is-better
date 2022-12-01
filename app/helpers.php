<?php

use DefStudio\Telegraph\Models\TelegraphBot;

if (!function_exists('chart_data_line')) {
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

if (!function_exists('bot_commands')) {
    function bot_commands() : array
    {
        $commands['short_key'] = 'short url with custom key';

        if (setting('channel.has', false)) {
            $commands['for_channel'] = 'make short for channel';
        }

        $commands['report'] = 'short report of last 7 days';
        $commands['stat'] = 'show stat for shorted url';
        $commands['short'] = 'short url';
        $commands['bulk'] = 'short bulk of links in text';

        return $commands;
    }
}

if (!function_exists('bot_update')) {
    function bot_update()
    {
        $bots = TelegraphBot::all();

        foreach ($bots as $bot) {
            $bot->unregisterCommands()->send();
            $v = $bot->registerCommands(bot_commands())->send();
        }
    }
}
