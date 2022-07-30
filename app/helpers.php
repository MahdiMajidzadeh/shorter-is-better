<?php

if (!function_exists('chart_data_line')) {
    function chart_data_line($data, $label, $col, $chartLabel)
    {
        return [
            "labels" => $data->pluck($label),
            "datasets" => [[
                "label" =>  $chartLabel,
                "data" => $data->pluck($col),
            ]]
        ];
    }
}