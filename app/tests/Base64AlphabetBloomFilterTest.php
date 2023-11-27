<?php declare(strict_types=1);

namespace Test;

use Nealio82\BloomFilter\Base64AlphabetBloomFilter;
use Nealio82\BloomFilter\Candidate;
use Nealio82\BloomFilter\UnsupportedCharacterException;
use PHPUnit\Framework\TestCase;
use Test\Doubles\FixedStringHasher;
use Test\Doubles\MismatchedStringHasher;
use Test\Doubles\NonHashingStringHasher;

final class Base64AlphabetBloomFilterTest extends TestCase
{
    public function test_empty_filter(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new FixedStringHasher('bbb')
        );

        self::assertTrue(
            $filter->definitelyNotInSet(new Candidate('aaa'))
        );
    }

    public function test_different_items(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        self::assertTrue(
            $filter->definitelyNotInSet(new Candidate('bbb'))
        );
    }

    /** @dataProvider alphabetCharacters */
    public function test_mismatched_uppercase(string $character): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new Candidate('abcdefghijklmnopqrstuvwxyz+/='));

        self::assertTrue(
            $filter->definitelyNotInSet(new Candidate(\strtoupper($character)))
        );
    }

    /** @dataProvider alphabetCharacters */
    public function test_mismatched_lowercase(string $character): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new Candidate('ABCDEFGHIJKLMNOPQRSTUVWXYZ+/='));

        self::assertTrue(
            $filter->definitelyNotInSet(new Candidate(\strtolower($character)))
        );
    }

    /** @dataProvider alphabetCharacters */
    public function test_matched_lowercase(string $character): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new Candidate('abcdefghijklmnopqrstuvwxyz+/='));

        self::assertFalse(
            $filter->definitelyNotInSet(new Candidate(\strtolower($character)))
        );
    }

    /** @dataProvider alphabetCharacters */
    public function test_matched_uppercase(string $character): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new Candidate('ABCDEFGHIJKLMNOPQRSTUVWXYZ+/='));

        self::assertFalse(
            $filter->definitelyNotInSet(new Candidate(\strtoupper($character)))
        );
    }

    /** @dataProvider specialCharacters */
    public function test_unmatched_special_characters(string $character): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new Candidate('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'));

        self::assertTrue(
            $filter->definitelyNotInSet(new Candidate(\strtoupper($character)))
        );
    }

    /** @dataProvider specialCharacters */
    public function test_matched_special_characters(string $character): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new Candidate('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+/='));

        self::assertFalse(
            $filter->definitelyNotInSet(new Candidate(\strtoupper($character)))
        );
    }

    public function test_item_is_in_storage(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new Candidate('aAa'));

        self::assertFalse(
            $filter->definitelyNotInSet(new Candidate('aAa'))
        );
    }

    public function test_numeric_item_in_storage(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new Candidate('0123456789'));

        self::assertFalse(
            $filter->definitelyNotInSet(new Candidate('0123456789'))
        );
    }

    public function test_numeric_may_be_storage(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new Candidate('0123456789'));

        self::assertFalse(
            $filter->definitelyNotInSet(new Candidate('123'))
        );
    }

    public function test_numeric_item_not_in_storage(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new Candidate('0123456789'));

        self::assertTrue(
            $filter->definitelyNotInSet(new Candidate('aaa'))
        );
    }

    public function test_similar_item_might_be_in_set(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new FixedStringHasher('teST')
        );

        $filter->store(new Candidate('teST'));

        self::assertFalse(
            $filter->definitelyNotInSet(new Candidate('tSeT'))
        );
    }

    public function test_full_alphanumeric_range_not_in_set(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $filter->store(new Candidate('aaa'));

        self::assertTrue(
            $filter->definitelyNotInSet(new Candidate($word))
        );
    }

    public function test_full_alphanumeric_range_maybe_in_set(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $filter->store(new Candidate($word));

        self::assertFalse(
            $filter->definitelyNotInSet(new Candidate(\strrev($word)))
        );
    }

    public function test_alphanumeric_range_subset_maybe_in_set(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $filter->store(new Candidate($word));

        self::assertFalse(
            $filter->definitelyNotInSet(new Candidate('helloHELLO1234'))
        );
    }

    public function test_ascii_plus_not_found_in_set(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/=';

        $filter->store(new Candidate($word));

        self::assertTrue(
            $filter->definitelyNotInSet(new Candidate('+'))
        );
    }

    public function test_ascii_string_not_match_ascii_plus(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/=';

        $filter->store(new Candidate('+'));

        self::assertTrue(
            $filter->definitelyNotInSet(new Candidate($word))
        );
    }

    public function test_ascii_plus_maybe_in_set(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new Candidate('+'));

        self::assertFalse(
            $filter->definitelyNotInSet(new Candidate('+'))
        );
    }

    public function test_ascii_slash_not_found_in_set(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+=';

        $filter->store(new Candidate($word));

        self::assertTrue(
            $filter->definitelyNotInSet(new Candidate('/'))
        );
    }

    public function test_ascii_string_not_match_ascii_slash(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+=';

        $filter->store(new Candidate('/'));

        self::assertTrue(
            $filter->definitelyNotInSet(new Candidate($word))
        );
    }

    public function test_ascii_slash_maybe_in_set(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $filter->store(new Candidate('/'));

        self::assertFalse(
            $filter->definitelyNotInSet(new Candidate('/'))
        );
    }

    public function test_store_complete_range_of_base64_characters(): void
    {
        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890+/=';

        $filter = new Base64AlphabetBloomFilter(
            new FixedStringHasher($word)
        );

        $filter->store(new Candidate($word));

        self::assertFalse(
            $filter->definitelyNotInSet(new Candidate('helloHELLO1234'))
        );
    }

    public function test_lookup_complete_range_of_base64_characters(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $word = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890+/=';

        $filter->store(new Candidate($word));

        self::assertFalse(
            $filter->definitelyNotInSet(new Candidate('helloHELLO1234'))
        );
    }

    public function test_not_in_set_when_any_character_not_found(): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new MismatchedStringHasher('test', 'testy')
        );

        $filter->store(new Candidate('test'));

        self::assertTrue(
            $filter->definitelyNotInSet(new Candidate('testy'))
        );
    }

    /** @dataProvider nonBase64AlphabetCharacters */
    public function test_non_alphanumeric_character_is_not_supported(string $character): void
    {
        $filter = new Base64AlphabetBloomFilter(
            new NonHashingStringHasher()
        );

        $this->expectException(UnsupportedCharacterException::class);
        $filter->store(new Candidate('aaa' . $character . 'bbb'));
    }

    public static function nonBase64AlphabetCharacters(): \Generator
    {
        $chars = \array_merge(
            \range(0, 42),
            \range(44, 46),
            \range(58, 60),
            \range(62, 64),
            \range(91, 96),
            \range(123, 127),
        );

        foreach ($chars as $char) {
            yield [\chr($char)];
        }
    }

    public static function alphabetCharacters(): \Generator
    {
        $chars = \range(97, 122);

        foreach ($chars as $char) {
            yield [\chr($char)];
        }
    }

    public static function specialCharacters(): array
    {
        return [
            ['+'],
            ['/'],
            ['='],
        ];
    }
}
