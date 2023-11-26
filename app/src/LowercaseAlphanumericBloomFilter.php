<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

final class LowercaseAlphanumericBloomFilter extends BloomFilter
{
    private const ASCII_LOWERCASE_CHARACTER_OFFSET = 87;

    private const ASCII_NUMERIC_CHARACTER_NUMBER_NINE_POSITION = 57;

    private const LOWER_CASE_ALPHANUMERIC_KEYSPACE_WIDTH = 36;

    private \SplFixedArray $cache;

    public function __construct(
        private readonly StringHasher $hasher
    ) {
        $this->cache = new \SplFixedArray(self::LOWER_CASE_ALPHANUMERIC_KEYSPACE_WIDTH);
    }

    protected function wordDefinitelyDoesNotExistInStorage(string $word): bool
    {
        $hash = $this->hasher->hash($word);

        foreach (\mb_str_split($hash) as $character) {
            if ($this->cache[self::getIndexPositionForChar($character)] !== true) {
                return true;
            }
        }

        return false;
    }

    protected function addItemToStorage(string $word): void
    {
        $hash = $this->hasher->hash($word);

        if (! \preg_match('/^[a-z0-9]+$/', $hash)) {
            throw new UnsupportedCharacterException(
                \sprintf(
                    'The provided hashing algorithm produced a hash (%s) containing disallowed characters',
                    $hash
                )
            );
        }

        foreach (\mb_str_split($hash) as $character) {
            $this->cache[self::getIndexPositionForChar($character)] = true;
        }
    }

    private static function getIndexPositionForChar(string $character): int
    {
        $position = \ord($character);

        if ($position <= self::ASCII_NUMERIC_CHARACTER_NUMBER_NINE_POSITION) {
            return (int) $character;
        }

        return $position - self::ASCII_LOWERCASE_CHARACTER_OFFSET;
    }
}
