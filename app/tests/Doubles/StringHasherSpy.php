<?php declare(strict_types=1);

namespace Test\Doubles;

use Nealio82\BloomFilter\StringHasher;

final class StringHasherSpy implements StringHasher
{
    private bool $wasCalled = false;

    public function __construct(
        private readonly StringHasher $innerHasher
    )
    {
    }

    public function hash(string $word): string
    {
        $this->wasCalled = true;
        return $this->innerHasher->hash($word);
    }

    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }
}