<?php declare(strict_types=1);

namespace Test\Doubles;

use Nealio82\BloomFilter\Candidate;
use Nealio82\BloomFilter\StringHasher;

final class FixedStringHasher implements StringHasher
{
    private string $hash;

    public function __construct(string $hash)
    {
        $this->hash = $hash;
    }

    public function hash(Candidate $candidate): string
    {
        return $this->hash;
    }
}
