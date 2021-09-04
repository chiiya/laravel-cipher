<?php

namespace Chiiya\LaravelCipher\Fields;

use ParagonIE\CipherSweet\Util;

class Integer extends BaseField
{
    public function serialize($value): string
    {
        return Util::intToString($value);
    }

    public function unserialize($value): int
    {
        return Util::stringToInt($value);
    }
}
