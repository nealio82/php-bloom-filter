<?php declare(strict_types=1);

namespace Test;

use Nealio82\BloomFilter\Hasher\OriginalStringHasher;
use Nealio82\BloomFilter\LowercaseAlphanumericBloomFilter;
use Nealio82\BloomFilter\MultiStrategyBloomFilter;
use Nealio82\BloomFilter\Value;
use PHPUnit\Framework\TestCase;
use Test\Doubles\BloomFilterSpy;
use Test\Doubles\StubBloomFilter;

final class MultiStrategyBloomFilterTest extends TestCase
{
    public function test_not_in_set_when_no_filters_find_item(): void
    {
        $filter = new MultiStrategyBloomFilter(
            new LowercaseAlphanumericBloomFilter(new OriginalStringHasher()),
            new LowercaseAlphanumericBloomFilter(new OriginalStringHasher()),
        );

        $filter->store(new Value('test'));

        self::assertTrue(
            $filter->definitelyNotInSet(new Value('bar'))
        );
    }

    public function test_return_after_first_definitely_not_in_set(): void
    {
        $spy1 = new BloomFilterSpy(new StubBloomFilter(false));
        $spy2 = new BloomFilterSpy(new StubBloomFilter(true));

        $filter = new MultiStrategyBloomFilter($spy1, $spy2);

        self::assertTrue(
            $filter->definitelyNotInSet(new Value('foo'))
        );

        self::assertTrue($spy1->wasCalled());
        self::assertFalse($spy2->wasCalled());
    }

    public function test_false_positive_negated_by_definitely_not_in_set_result(): void
    {
        $spy1 = new BloomFilterSpy(new StubBloomFilter(true));
        $spy2 = new BloomFilterSpy(new StubBloomFilter(true));
        $spy3 = new BloomFilterSpy(new StubBloomFilter(false));

        $filter = new MultiStrategyBloomFilter($spy1, $spy2, $spy3);

        self::assertTrue(
            $filter->definitelyNotInSet(new Value('foo'))
        );
    }

    public function test_next_filter_is_called_if_potential_match_found(): void
    {
        $spy1 = new BloomFilterSpy(new StubBloomFilter(true));
        $spy2 = new BloomFilterSpy(new StubBloomFilter(true));

        $filter = new MultiStrategyBloomFilter($spy1, $spy2);

        $filter->definitelyNotInSet(new Value('foo'));

        self::assertTrue($spy1->wasCalled());
        self::assertTrue($spy2->wasCalled());
    }

    public function test_potential_match_is_found_in_all_filters(): void
    {
        $spy1 = new BloomFilterSpy(new StubBloomFilter(true));
        $spy2 = new BloomFilterSpy(new StubBloomFilter(true));

        $filter = new MultiStrategyBloomFilter($spy1, $spy2);

        self::assertFalse(
            $filter->definitelyNotInSet(new Value('foo'))
        );
    }

    public function test_item_is_added_to_all_filter_storage(): void
    {
        $spy1 = new BloomFilterSpy(new StubBloomFilter(false));
        $spy2 = new BloomFilterSpy(new StubBloomFilter(true));

        $filter = new MultiStrategyBloomFilter($spy1, $spy2);

        self::assertSame('', $spy1->lastStoredWord);
        self::assertSame('', $spy2->lastStoredWord);

        $filter->store(new Value('foo'));

        self::assertSame('foo', $spy1->lastStoredWord);
        self::assertSame('foo', $spy2->lastStoredWord);
    }
}
