<?php

namespace Chiiya\LaravelCipher;

use Chiiya\LaravelCipher\Fields\BaseField;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\CompoundIndex;
use ParagonIE\CipherSweet\EncryptedRow as CipherSweetEncryptedRow;

class EncryptedRow extends CipherSweetEncryptedRow
{
    /** @var BaseField[] */
    protected array $fields;

    /**
     * @param BaseField[] $fields
     */
    public function setFields(array $fields): void
    {
        $this->fields = $fields;

        foreach ($fields as $field) {
            $this->addTextField($field->getName());
        }
    }

    public function getAllBlindIndexes(array $row): array
    {
        $return = [];

        foreach ($this->blindIndexes as $column => $blindIndexes) {
            if ($row[$column] === null && $this->fields[$column]->isNullable()) {
                continue;
            }

            /** @var BlindIndex $blindIndex */
            foreach ($blindIndexes as $blindIndex) {
                $return[$blindIndex->getName()] = $this->calcBlindIndex(
                    [$column => $this->fields[$column]->serialize($row[$column])],
                    $column,
                    $blindIndex
                );
            }
        }

        $row = collect($row)->map(fn ($value, string $key) => $this->fields[$key]->serialize($value))->all();

        /**
         * @var string $name
         * @var CompoundIndex $compoundIndex
         */
        foreach ($this->compoundIndexes as $name => $compoundIndex) {
            $return[$name] = $this->calcCompoundIndex($row, $compoundIndex);
        }

        return $return;
    }

    public function getBlindIndex($indexName, array $row)
    {
        $row = collect($row)->map(fn ($value, string $key) => $this->fields[$key]->serialize($value))->all();

        return parent::getBlindIndex($indexName, $row);
    }
}
