<?php declare(strict_types=1);

namespace Test\Doubles;

use Nealio82\BloomFilter\BloomFilter;
use Nealio82\BloomFilter\Candidate;

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

    protected function candidateDefinitelyDoesNotExistInStorage(Candidate $candidate): bool
    {
        $this->wasCalled = true;

        return $this->innerFilter->definitelyNotInSet($candidate);
    }

    protected function addItemToStorage(Candidate $candidate): void
    {
        $this->innerFilter->store($candidate);
        $this->lastStoredWord = $candidate->string();
    }
}
