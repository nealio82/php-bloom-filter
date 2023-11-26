<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

abstract class BloomFilter
{
    public function definitelyNotInSet(string $word): bool
    {
        return $this->wordDefinitelyDoesNotExistInStorage($word);
    }

    public function store(string $word): void
    {
        $this->addItemToStorage($word);
    }

    abstract protected function wordDefinitelyDoesNotExistInStorage(string $word): bool;

    abstract protected function addItemToStorage(string $word): void;
}
