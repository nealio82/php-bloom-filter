<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

final readonly class Candidate
{
    public function __construct(
        public string $word
    ) {
    }
}
