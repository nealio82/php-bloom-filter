<?php declare(strict_types=1);

namespace Test;

use Nealio82\BloomFilter\BinaryIntegerBloomFilter;
use Nealio82\BloomFilter\Value;
use PHPUnit\Framework\TestCase;

final class BinaryIntegerBloomFilterTest extends TestCase
{
    public function test_empty_filter(): void
    {
        $filter = new BinaryIntegerBloomFilter();

        self::assertTrue(
            $filter->definitelyNotInSet(new Value(123))
        );
    }

    /** @dataProvider integers */
    public function test_exact_match_in_set(int $number): void
    {
        $filter = new BinaryIntegerBloomFilter();

        $filter->store(new Value($number));

        self::assertFalse(
            $filter->definitelyNotInSet(new Value($number))
        );
    }

    /** @dataProvider integers */
    public function test_max_integer_matches_all(int $number): void
    {
        $filter = new BinaryIntegerBloomFilter();

        $filter->store(new Value(255));

        self::assertFalse(
            $filter->definitelyNotInSet(new Value($number))
        );
    }

    /** @dataProvider positiveIntegers */
    public function test_zero_integer_matches_nothing_above_zero(int $number): void
    {
        $filter = new BinaryIntegerBloomFilter();

        $filter->store(new Value(0));

        self::assertTrue(
            $filter->definitelyNotInSet(new Value($number))
        );
    }

    public function test_similar_match_in_set(): void
    {
        $filter = new BinaryIntegerBloomFilter();

        $filter->store(new Value(1));
        $filter->store(new Value(2));
        $filter->store(new Value(4));

        self::assertFalse(
            $filter->definitelyNotInSet(new Value(3))
        );
    }

    public function test_definitely_not_in_set(): void
    {
        $filter = new BinaryIntegerBloomFilter();

        $filter->store(new Value(1));
        $filter->store(new Value(4));

        self::assertTrue(
            $filter->definitelyNotInSet(new Value(2))
        );

        self::assertTrue(
            $filter->definitelyNotInSet(new Value(3))
        );

        self::assertTrue(
            $filter->definitelyNotInSet(new Value(6))
        );
    }

    public function test_binary_string_reversed_not_matches(): void
    {
        $bin = '100';

        $filter = new BinaryIntegerBloomFilter();

        $filter->store(new Value(\bindec($bin)));

        self::assertTrue(
            $filter->definitelyNotInSet(new Value(\bindec(\strrev($bin))))
        );
    }

    public static function positiveIntegers(): \Generator
    {
        yield from \array_map(static fn (int $item) => [$item], \range(1, 255));
    }

    public static function integers(): \Generator
    {
        yield [0];
        yield from self::positiveIntegers();
    }
}
