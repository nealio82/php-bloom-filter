<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

final class BinaryIntegerBloomFilter extends BloomFilter
{
    private int $filter = 0;

    protected function candidateDefinitelyDoesNotExistInStorage(Value $value): bool
    {
        if ($this->filter === $value->integer()) {
            return false;
        }

        $filterBin = \strrev(\decbin($this->filter));
        $valueBin = \strrev(\decbin($value->integer()));

        foreach (\str_split($valueBin) as $position => $char) {
            if ($char === '1') {
                if (! isset($filterBin[$position]) || $filterBin[$position] !== '1') {
                    return true;
                }
            }
        }

        return false;
    }

    protected function addItemToStorage(Value $value): void
    {
        $this->filter = $this->filter | $value->integer();
    }
}
