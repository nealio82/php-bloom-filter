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

    protected function candidateDefinitelyDoesNotExistInStorage(Value $value): bool
    {
        foreach ($this->filters as $filter) {
            if (true === $filter->candidateDefinitelyDoesNotExistInStorage($value)) {
                return true;
            }
        }

        return false;
    }

    protected function addItemToStorage(Value $value): void
    {
        foreach ($this->filters as $filter) {
            $filter->addItemToStorage($value);
        }
    }
}
