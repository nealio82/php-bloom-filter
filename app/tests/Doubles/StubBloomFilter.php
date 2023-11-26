<?php declare(strict_types=1);

namespace Test\Doubles;

use Nealio82\BloomFilter\BloomFilter;

final class StubBloomFilter extends BloomFilter
{
    public function __construct(
        private readonly bool $willFindWords
    ) {
    }

    protected function wordDefinitelyDoesNotExistInStorage(string $word): bool
    {
        return ! $this->willFindWords;
    }

    protected function addItemToStorage(string $word): void
    {
    }
}
