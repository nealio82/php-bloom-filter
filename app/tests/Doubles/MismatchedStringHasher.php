<?php declare(strict_types=1);

namespace Test\Doubles;

use Nealio82\BloomFilter\StringHasher;

final class MismatchedStringHasher implements StringHasher
{
    private array $hashes;

    private int $position = 0;

    public function __construct(string ...$hashes)
    {
        $this->hashes = $hashes;
    }

    public function hash(string $word): string
    {
        return $this->hashes[$this->position++];
    }
}
