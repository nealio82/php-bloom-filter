<?php declare(strict_types=1);

namespace Test;

use Nealio82\BloomFilter\FullAsciiAlphabetBloomFilter;
use Nealio82\BloomFilter\Hasher\OriginalStringHasher;
use Nealio82\BloomFilter\UnsupportedCharacterException;
use Nealio82\BloomFilter\Value;
use PHPUnit\Framework\TestCase;
use Test\Doubles\FixedStringHasher;
use Test\Doubles\MismatchedStringHasher;

final class FullAsciiAlphabetBloomFilterTest extends TestCase
{
    public function test_empty_filter(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        self::assertTrue(
            $filter->definitelyNotInSet(new Value('aaa'))
        );
    }

    public function test_different_items(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        self::assertTrue(
            $filter->definitelyNotInSet(new Value('bbb'))
        );
    }

    /** @dataProvider alphabetCharacters */
    public function test_matched_characters(string $character): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        foreach (\array_map(static fn (array $item) => $item[0], self::alphabetCharacters()) as $character) {
            $filter->store(new Value($character));
        }

        self::assertFalse(
            $filter->definitelyNotInSet(new Value(\strtolower($character)))
        );
    }

    public function test_item_is_in_storage(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $filter->store(new Value('aAa'));

        self::assertFalse(
            $filter->definitelyNotInSet(new Value('aAa'))
        );
    }

    public function test_numeric_item_in_storage(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $filter->store(new Value('0123456789'));

        self::assertFalse(
            $filter->definitelyNotInSet(new Value('0123456789'))
        );
    }

    public function test_numeric_may_be_storage(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $filter->store(new Value('0123456789'));

        self::assertFalse(
            $filter->definitelyNotInSet(new Value('123'))
        );
    }

    public function test_numeric_item_not_in_storage(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $filter->store(new Value('0123456789'));

        self::assertTrue(
            $filter->definitelyNotInSet(new Value('aaa'))
        );
    }

    public function test_similar_item_might_be_in_set(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new FixedStringHasher('teST')
        );

        $filter->store(new Value('teST'));

        self::assertFalse(
            $filter->definitelyNotInSet(new Value('tSeT'))
        );
    }

    public function test_full_alphanumeric_range_not_in_set(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $filter->store(new Value('aaa'));

        self::assertTrue(
            $filter->definitelyNotInSet(new Value($word))
        );
    }

    public function test_full_alphanumeric_range_maybe_in_set(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $filter->store(new Value($word));

        self::assertFalse(
            $filter->definitelyNotInSet(new Value(\strrev($word)))
        );
    }

    public function test_alphanumeric_range_subset_maybe_in_set(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $filter->store(new Value($word));

        self::assertFalse(
            $filter->definitelyNotInSet(new Value('helloHELLO1234'))
        );
    }

    public function test_ascii_plus_not_found_in_set(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/=';

        $filter->store(new Value($word));

        self::assertTrue(
            $filter->definitelyNotInSet(new Value('+'))
        );
    }

    public function test_ascii_string_not_match_ascii_plus(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/=';

        $filter->store(new Value('+'));

        self::assertTrue(
            $filter->definitelyNotInSet(new Value($word))
        );
    }

    public function test_ascii_plus_maybe_in_set(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $filter->store(new Value('+'));

        self::assertFalse(
            $filter->definitelyNotInSet(new Value('+'))
        );
    }

    public function test_ascii_slash_not_found_in_set(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+=';

        $filter->store(new Value($word));

        self::assertTrue(
            $filter->definitelyNotInSet(new Value('/'))
        );
    }

    public function test_ascii_string_not_match_ascii_slash(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+=';

        $filter->store(new Value('/'));

        self::assertTrue(
            $filter->definitelyNotInSet(new Value($word))
        );
    }

    public function test_ascii_slash_maybe_in_set(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $filter->store(new Value('/'));

        self::assertFalse(
            $filter->definitelyNotInSet(new Value('/'))
        );
    }

    public function test_store_complete_range_of_base64_characters(): void
    {
        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890+/=';

        $filter = new FullAsciiAlphabetBloomFilter(
            new FixedStringHasher($word)
        );

        $filter->store(new Value($word));

        self::assertFalse(
            $filter->definitelyNotInSet(new Value('helloHELLO1234'))
        );
    }

    public function test_lookup_complete_range_of_base64_characters(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890+/=';

        $filter->store(new Value($word));

        self::assertFalse(
            $filter->definitelyNotInSet(new Value('helloHELLO1234'))
        );
    }

    public function test_not_in_set_when_any_character_not_found(): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new MismatchedStringHasher('test', 'testy')
        );

        $filter->store(new Value('test'));

        self::assertTrue(
            $filter->definitelyNotInSet(new Value('testy'))
        );
    }

    /** @dataProvider nonFullAsciiAlphabetCharacters */
    public function test_non_alphanumeric_character_is_not_supported(string $character): void
    {
        $filter = new FullAsciiAlphabetBloomFilter(
            new OriginalStringHasher()
        );

        $this->expectException(UnsupportedCharacterException::class);
        $filter->store(new Value('aaa' . $character . 'bbb'));
    }

    public static function nonFullAsciiAlphabetCharacters(): array
    {
        return [
            ['👋'],
        ];
    }

    public static function alphabetCharacters(): array
    {
        $chars = \range(0, 127);

        return \array_map(static fn (int $item) => [\chr($item)], $chars);
    }
}
