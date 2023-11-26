<?php declare(strict_types=1);

namespace Test\Doubles;

use Nealio82\BloomFilter\BloomFilter;

final class BloomFilterSpy extends BloomFilter
{
    public string $lastStoredWord = '';

    private bool $wasCalled = false;

    public function __construct(
        private readonly BloomFilter $innerFilter
    ) {
    }

    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }

    protected function wordDefinitelyDoesNotExistInStorage(string $word): bool
    {
        $this->wasCalled = true;

        return $this->innerFilter->definitelyNotInSet($word);
    }

    protected function addItemToStorage(string $word): void
    {
        $this->innerFilter->store($word);
        $this->lastStoredWord = $word;
    }
}
