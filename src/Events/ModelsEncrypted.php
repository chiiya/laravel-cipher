<?php

namespace Chiiya\LaravelCipher\Events;

class ModelsEncrypted
{
    public string $model;
    public int $count;

    public function __construct(string $model, int $count)
    {
        $this->model = $model;
        $this->count = $count;
    }
}
