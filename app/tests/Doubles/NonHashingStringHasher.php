<?php declare(strict_types=1);

namespace Test\Doubles;

use Nealio82\BloomFilter\StringCandidate;
use Nealio82\BloomFilter\StringHasher;

final class NonHashingStringHasher implements StringHasher
{
    public function hash(StringCandidate $candidate): string
    {
        return $candidate->value();
    }
}
