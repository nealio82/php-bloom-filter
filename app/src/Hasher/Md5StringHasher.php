<?php declare(strict_types=1);

namespace Nealio82\BloomFilter\Hasher;

use Nealio82\BloomFilter\StringHasher;
use Nealio82\BloomFilter\Value;

final readonly class Md5StringHasher implements StringHasher
{
    public function hash(Value $value): string
    {
        return \md5($value->string());
    }
}
