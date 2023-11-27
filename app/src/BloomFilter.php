<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

abstract class BloomFilter
{
    public function definitelyNotInSet(Value $value): bool
    {
        return $this->candidateDefinitelyDoesNotExistInStorage($value);
    }

    public function store(Value $value): void
    {
        $this->addItemToStorage($value);
    }

    abstract protected function candidateDefinitelyDoesNotExistInStorage(Value $value): bool;

    abstract protected function addItemToStorage(Value $value): void;
}
