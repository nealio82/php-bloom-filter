<?php declare(strict_types=1);

namespace Test\Doubles;

use Nealio82\BloomFilter\StringHasher;
use Nealio82\BloomFilter\Value;

final class MismatchedStringHasher implements StringHasher
{
    private array $hashes;

    private int $position = 0;

    public function __construct(string ...$hashes)
    {
        $this->hashes = $hashes;
    }

    public function hash(Value $value): string
    {
        return $this->hashes[$this->position++];
    }
}
