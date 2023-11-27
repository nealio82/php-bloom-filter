<?php declare(strict_types=1);

namespace Test;

use Nealio82\BloomFilter\Value;
use PHPUnit\Framework\TestCase;
use Test\Doubles\BloomFilterSpy;
use Test\Doubles\StubBloomFilter;

final class BloomFilterTest extends TestCase
{
    public function test_item_is_added_to_storage(): void
    {
        $filter = new BloomFilterSpy(new StubBloomFilter(false));

        $word = \sha1((string) \time());

        $filter->store(new Value($word));

        self::assertSame($word, $filter->lastStoredWord);
    }
}
