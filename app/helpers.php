<?php

use Morilog\Jalali\Jalalian;
use Illuminate\Support\Carbon;

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

if (! function_exists('versioned_asset')) {
    /**
     * Url of a file in public/ with a `?v=` fingerprint of its content, so a
     * new build gets a new url and no browser or CDN keeps serving the
     * previous stylesheet. Falls back to the plain url when the file is
     * missing (the Tailwind build has not run yet).
     */
    function versioned_asset(string $path): string
    {
        static $versions = [];

        $file = public_path($path);

        if (! array_key_exists($file, $versions)) {
            $versions[$file] = is_file($file) ? substr(md5_file($file), 0, 8) : null;
        }

        return $versions[$file] ? asset($path).'?v='.$versions[$file] : asset($path);
    }
}
