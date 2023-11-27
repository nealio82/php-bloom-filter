<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

interface StringHasher
{
    public function hash(Value $value): string;
}
