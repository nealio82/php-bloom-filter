<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

final class MultiStrategyBloomFilter extends BloomFilter
{
    private array $filters;

    public function __construct(
        BloomFilter ...$filters
    ) {
        $this->filters = $filters;
    }

    protected function candidateDefinitelyDoesNotExistInStorage(Candidate $candidate): bool
    {
        foreach ($this->filters as $filter) {
            if (true === $filter->candidateDefinitelyDoesNotExistInStorage($candidate)) {
                return true;
            }
        }

        return false;
    }

    protected function addItemToStorage(Candidate $candidate): void
    {
        foreach ($this->filters as $filter) {
            $filter->addItemToStorage($candidate);
        }
    }
}
