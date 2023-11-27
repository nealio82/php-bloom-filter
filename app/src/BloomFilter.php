<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

abstract class BloomFilter
{
    public function definitelyNotInSet(Candidate $candidate): bool
    {
        return $this->candidateDefinitelyDoesNotExistInStorage($candidate);
    }

    public function store(Candidate $candidate): void
    {
        $this->addItemToStorage($candidate);
    }

    abstract protected function candidateDefinitelyDoesNotExistInStorage(Candidate $candidate): bool;

    abstract protected function addItemToStorage(Candidate $candidate): void;
}
