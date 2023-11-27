<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

interface Candidate
{
    public function value(): string|int;
}