<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

final class Base64AlphabetBloomFilter extends BloomFilter
{
    private const BASE64_ALPHABET_KEYSPACE_WIDTH_PLUS_PADDING_CHARACTER = 65;

    private \SplFixedArray $cache;

    public function __construct(
        private readonly StringHasher $hasher
    ) {
        $this->cache = new \SplFixedArray(self::BASE64_ALPHABET_KEYSPACE_WIDTH_PLUS_PADDING_CHARACTER);
    }

    protected function wordDefinitelyDoesNotExistInStorage(string $word): bool
    {
        $hash = $this->hasher->hash($word);

        foreach (\str_split($hash) as $character) {
            if ($this->cache[self::getIndexPositionForChar($character)] !== true) {
                return true;
            }
        }

        return false;
    }

    protected function addItemToStorage(string $word): void
    {
        $hash = $this->hasher->hash($word);

        if (! \preg_match('/^[a-zA-Z0-9+\/=]+$/', $hash)) {
            throw new UnsupportedCharacterException(
                \sprintf(
                    'The provided hashing algorithm produced a hash (%s) containing disallowed characters',
                    $hash
                )
            );
        }

        foreach (\str_split($hash) as $character) {
            $this->cache[self::getIndexPositionForChar($character)] = true;
        }
    }

    private static function getIndexPositionForChar(string $character): int
    {
        $positions
            = \array_merge(
                \array_map(static fn (int $item) => (string) $item, \range(0, 9)),
                \array_map(static fn (int $item) => \chr($item), \range(65, 90)),
                \array_map(static fn (int $item) => \chr($item), \range(97, 122)),
                [
                    '+',
                    '/',
                    '=',
                ]
            );

        return \array_search($character, $positions, true);
    }
}
