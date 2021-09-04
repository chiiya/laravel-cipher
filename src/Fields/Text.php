<?php

namespace Chiiya\LaravelCipher\Fields;

class Text extends BaseField
{
    public function serialize($value): string
    {
        return (string) $value;
    }

    public function unserialize($value)
    {
        return (string) $value;
    }
}
