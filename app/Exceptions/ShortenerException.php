<?php

namespace App\Exceptions;

use RuntimeException;

class ShortenerException extends RuntimeException
{
    public static function invalidUrl(): self
    {
        return new self(__('The url is not valid.'));
    }

    public static function invalidKey(): self
    {
        return new self(__('The key may only contain letters, digits, dashes and underscores.'));
    }

    public static function keyTaken(string $key): self
    {
        return new self(__('The key ":key" is already in use.', ['key' => $key]));
    }
}
