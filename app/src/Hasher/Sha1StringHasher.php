<?php declare(strict_types=1);

namespace Nealio82\BloomFilter\Hasher;

use Nealio82\BloomFilter\StringHasher;
use Nealio82\BloomFilter\Value;

final readonly class Sha1StringHasher implements StringHasher
{
    public function hash(Value $value): string
    {
        return \sha1($value->string());
    }
}
