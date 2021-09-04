<?php

namespace Chiiya\LaravelCipher\Fields;

use ParagonIE\CipherSweet\Util;

class Boolean extends BaseField
{
    public function serialize($value): string
    {
        return Util::boolToChr($value);
    }

    public function unserialize($value): ?bool
    {
        return Util::chrToBool($value);
    }
}
