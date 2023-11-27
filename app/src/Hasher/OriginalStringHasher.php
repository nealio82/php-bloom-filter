<?php declare(strict_types=1);

namespace Nealio82\BloomFilter\Hasher;

use Nealio82\BloomFilter\StringHasher;
use Nealio82\BloomFilter\Value;

final class OriginalStringHasher implements StringHasher
{
    public function hash(Value $value): string
    {
        return $value->string();
    }
}
