<?php declare(strict_types=1);

namespace Test;

use Nealio82\BloomFilter\StringCandidate;
use Nealio82\BloomFilter\LowercaseAlphanumericBloomFilter;
use Nealio82\BloomFilter\UnsupportedCharacterException;
use PHPUnit\Framework\TestCase;
use Test\Doubles\FixedStringHasher;
use Test\Doubles\MismatchedStringHasher;
use Test\Doubles\NonHashingStringHasher;

final class LowercaseAlphanumericBloomFilterTest extends TestCase
{
    public function test_empty_filter(): void
    {
        $filter = new LowercaseAlphanumericBloomFilter(
            new FixedStringHasher('bbb')
        );

        self::assertTrue(
            $filter->definitelyNotInSet(new StringCandidate('aaa'))
        );
    }

    public function test_different_items(): void
    {
        $filter = new LowercaseAlphanumericBloomFilter(
            new NonHashingStringHasher()
        );

        self::assertTrue(
            $filter->definitelyNotInSet(new StringCandidate('bbb'))
        );
    }

    public function test_item_is_in_storage(): void
    {
        $filter = new LowercaseAlphanumericBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new StringCandidate('aaa'));

        self::assertFalse(
            $filter->definitelyNotInSet(new StringCandidate('aaa'))
        );
    }

    public function test_numeric_item_in_storage(): void
    {
        $filter = new LowercaseAlphanumericBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new StringCandidate('0123456789'));

        self::assertFalse(
            $filter->definitelyNotInSet(new StringCandidate('0123456789'))
        );
    }

    public function test_numeric_may_be_storage(): void
    {
        $filter = new LowercaseAlphanumericBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new StringCandidate('0123456789'));

        self::assertFalse(
            $filter->definitelyNotInSet(new StringCandidate('123'))
        );
    }

    public function test_numeric_item_not_in_storage(): void
    {
        $filter = new LowercaseAlphanumericBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new StringCandidate('0123456789'));

        self::assertTrue(
            $filter->definitelyNotInSet(new StringCandidate('aaa'))
        );
    }

    public function test_similar_item_might_be_in_set(): void
    {
        $filter = new LowercaseAlphanumericBloomFilter(
            new FixedStringHasher('test')
        );

        $filter->store(new StringCandidate('test'));

        self::assertFalse(
            $filter->definitelyNotInSet(new StringCandidate('tset'))
        );
    }

    public function test_full_alphanumeric_range_not_in_set(): void
    {
        $filter = new LowercaseAlphanumericBloomFilter(
            new NonHashingStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyz0123456789';

        $filter->store(new StringCandidate('aaa'));

        self::assertTrue(
            $filter->definitelyNotInSet(new StringCandidate($word))
        );
    }

    public function test_full_alphanumeric_range_maybe_in_set(): void
    {
        $filter = new LowercaseAlphanumericBloomFilter(
            new NonHashingStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyz0123456789';

        $filter->store(new StringCandidate($word));

        self::assertFalse(
            $filter->definitelyNotInSet(new StringCandidate(\strrev($word)))
        );
    }

    public function test_alphanumeric_range_subset_maybe_in_set(): void
    {
        $filter = new LowercaseAlphanumericBloomFilter(
            new NonHashingStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyz0123456789';

        $filter->store(new StringCandidate($word));

        self::assertFalse(
            $filter->definitelyNotInSet(new StringCandidate('hello1234'))
        );
    }

    public function test_not_in_set_when_any_character_not_found(): void
    {
        $filter = new LowercaseAlphanumericBloomFilter(
            new MismatchedStringHasher('test', 'testy')
        );

        $filter->store(new StringCandidate('test'));

        self::assertTrue(
            $filter->definitelyNotInSet(new StringCandidate('testy'))
        );
    }

    /** @dataProvider nonLowercaseCharacters */
    public function test_non_lowercase_character_is_not_supported(string $character): void
    {
        $filter = new LowercaseAlphanumericBloomFilter(
            new NonHashingStringHasher()
        );

        $this->expectException(UnsupportedCharacterException::class);
        $filter->store(new StringCandidate('aaa' . $character . 'bbb'));
    }

    /** @dataProvider nonAlphanumericCharacters */
    public function test_non_alphanumeric_character_is_not_supported(string $character): void
    {
        $filter = new LowercaseAlphanumericBloomFilter(
            new NonHashingStringHasher()
        );

        $this->expectException(UnsupportedCharacterException::class);
        $filter->store(new StringCandidate('aaa' . $character . 'bbb'));
    }

    public static function nonLowercaseCharacters(): \Generator
    {
        foreach (\range(65, 90) as $char) {
            yield [\chr($char)];
        }
    }

    public static function nonAlphanumericCharacters(): \Generator
    {
        $chars = \array_merge(
            \range(0, 47),
            \range(58, 64),
            \range(91, 96),
            \range(123, 127),
        );

        foreach ($chars as $char) {
            yield [\chr($char)];
        }
    }
}
