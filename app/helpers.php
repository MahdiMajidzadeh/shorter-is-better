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
    function bot_commands()
    {
        $commands = [
            'shortkey' => 'short url with custom key',
            'report'   => 'short report of last 7 days',
            'short'    => 'short url',
            'stat'     => 'show stat for shorted url',
            //            'bulk'       => 'short bulk of links in text',
            //            'auth'       => 'authenticate bot with your account',
        ];

        if (setting('channel.has', false)) {
            $commands['forchannel'] = 'make short for channel';
        }

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
