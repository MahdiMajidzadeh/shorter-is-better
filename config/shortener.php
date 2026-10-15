<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Redirect prefix
    |--------------------------------------------------------------------------
    |
    | Short links are served from /{prefix}/{key} on every project domain.
    | Existing links were issued under "s"; changing this breaks them.
    |
    */

    'prefix' => 's',

    /*
    |--------------------------------------------------------------------------
    | Key generation
    |--------------------------------------------------------------------------
    |
    | Generated keys are random strings of `key_length` characters drawn from
    | `alphabet`. On a collision the generator retries, and after
    | `max_attempts` collisions it grows the key by one character.
    |
    */

    'key_length'   => 5,
    'alphabet'     => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
    'max_attempts' => 10,

    /*
    |--------------------------------------------------------------------------
    | Title fetching
    |--------------------------------------------------------------------------
    */

    'fetch_timeout' => 5,
    'user_agent'    => 'Mozilla/5.0 (compatible; ShorterIsBetter/2.0; +https://github.com/MahdiMajidzadeh/shorter-is-better)',

];
