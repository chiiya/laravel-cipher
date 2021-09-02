<?php

namespace Chiiya\LaravelCipher\Eloquent;

interface Encryptable
{
    public function getAadValue(): ?string;
    public function getTable();
}
