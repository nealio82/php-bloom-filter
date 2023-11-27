<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

final readonly class IntegerCandidate implements Candidate
{
    public function __construct(
        private int $number
    ) {
    }

    public function value(): int
    {
        return $this->number;
    }
}
