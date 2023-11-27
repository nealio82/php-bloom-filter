<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

final class BinaryIntegerBloomFilter extends BloomFilter
{
    private int $filter = 0;

    protected function candidateDefinitelyDoesNotExistInStorage(Candidate $candidate): bool
    {
        if ($this->filter === $candidate->integer()) {
            return false;
        }

        $filterBin = \strrev(\decbin($this->filter));
        $candidateBin = \strrev(\decbin($candidate->integer()));

        foreach (\str_split($candidateBin) as $position => $char) {
            if ($char === '1') {
                if (! isset($filterBin[$position]) || $filterBin[$position] !== '1') {
                    return true;
                }
            }
        }

        return false;
    }

    protected function addItemToStorage(Candidate $candidate): void
    {
        $this->filter = $this->filter | $candidate->integer();
    }
}
