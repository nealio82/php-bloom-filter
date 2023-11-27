<?php declare(strict_types=1);

namespace Test\Doubles;

use Nealio82\BloomFilter\BloomFilter;
use Nealio82\BloomFilter\Candidate;

final class StubBloomFilter extends BloomFilter
{
    public function __construct(
        private readonly bool $willFindCandidates
    ) {
    }

    protected function candidateDefinitelyDoesNotExistInStorage(Candidate $candidate): bool
    {
        return ! $this->willFindCandidates;
    }

    protected function addItemToStorage(Candidate $candidate): void
    {
    }
}
