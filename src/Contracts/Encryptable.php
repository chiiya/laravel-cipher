<?php

namespace Chiiya\LaravelCipher\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Encryptable
{
    public function blindIndexes(): MorphMany;

    public function getAadValue(): ?string;

    public function encryptAll(int $chunkSize = 500): int;

    public function encrypt(): void;

    public function getBlindIndexes(): array;

    public function getCompoundIndexes(): array;

    public function encryptedTypes(): array;

    public function getTable();

    public function attributesToArray();

    public function getKey();
}
