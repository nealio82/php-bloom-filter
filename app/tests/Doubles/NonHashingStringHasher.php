<?php declare(strict_types=1);

namespace Test\Doubles;

use Nealio82\BloomFilter\Candidate;
use Nealio82\BloomFilter\StringHasher;

final class NonHashingStringHasher implements StringHasher
{
    public function hash(Candidate $candidate): string
    {
        return $candidate->word;
    }
}
