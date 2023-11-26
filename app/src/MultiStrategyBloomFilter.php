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

    protected function wordDefinitelyDoesNotExistInStorage(string $word): bool
    {
        foreach ($this->filters as $filter) {
            if (true === $filter->wordDefinitelyDoesNotExistInStorage($word)) {
                return true;
            }
        }

        return false;
    }

    protected function addItemToStorage(string $word): void
    {
        foreach ($this->filters as $filter) {
            $filter->addItemToStorage($word);
        }
    }
}
