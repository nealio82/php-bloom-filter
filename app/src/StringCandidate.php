<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

final readonly class StringCandidate implements Candidate
{
    public function __construct(
        private string $word
    )
    {
    }

    public function value(): string
    {
        return $this->word;
    }
}
