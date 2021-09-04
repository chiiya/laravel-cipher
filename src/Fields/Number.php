<?php

namespace Chiiya\LaravelCipher\Fields;

use ParagonIE\CipherSweet\Util;

class Number extends BaseField
{
    public function serialize($value): string
    {
        return Util::floatToString($value);
    }

    public function unserialize($value): float
    {
        return Util::stringToFloat($value);
    }
}
