<?php declare(strict_types=1);

namespace Test\Doubles;

use Nealio82\BloomFilter\StringHasher;
use Nealio82\BloomFilter\Value;

final class FixedStringHasher implements StringHasher
{
    private string $hash;

    public function __construct(string $hash)
    {
        $this->hash = $hash;
    }

    public function hash(Value $value): string
    {
        return $this->hash;
    }
}
