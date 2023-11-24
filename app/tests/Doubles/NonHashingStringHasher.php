<?php declare(strict_types=1);

namespace Test\Doubles;

use Nealio82\BloomFilter\StringHasher;

final class NonHashingStringHasher implements StringHasher
{
    public function hash(string $word): string
    {
        return $word;
    }
}