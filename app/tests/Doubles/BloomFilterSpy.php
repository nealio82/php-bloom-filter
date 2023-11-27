<?php declare(strict_types=1);

namespace Test\Doubles;

use Nealio82\BloomFilter\BloomFilter;
use Nealio82\BloomFilter\Value;

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

    protected function candidateDefinitelyDoesNotExistInStorage(Value $value): bool
    {
        $this->wasCalled = true;

        return $this->innerFilter->definitelyNotInSet($value);
    }

    protected function addItemToStorage(Value $value): void
    {
        $this->innerFilter->store($value);
        $this->lastStoredWord = $value->string();
    }
}
