<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

final class BloomFilter
{
    private \SplFixedArray $cache;

    private array $hashers;

    private const ASCII_LOWERCASE_CHARACTER_OFFSET = 87;

    private const ASCII_NUMERIC_CHARACTER_NUMBER_NINE_POSITION = 57;

    private const LOWER_CASE_ALPHANUMERIC_KEYSPACE_WIDTH = 36;

    public function __construct(
        StringHasher ...$hashers
    )
    {
        $this->hashers = $hashers;
        $this->createStorage(\count($hashers));
    }

    public function definitelyNotInSet(string $word): bool
    {
        foreach ($this->hashers as $index => $hasher) {
            $hash = $hasher->hash($word);

            foreach (\str_split($hash) as $character) {
                if ($this->cache[$index][self::getIndexPositionForChar($character)] !== true) {
                    return true;
                }
            }
        }

        return false;
    }

    public function store(string $word): void
    {
        foreach ($this->hashers as $index => $hasher) {
            $hash = $hasher->hash($word);

            foreach (\str_split($hash) as $character) {
                $this->cache[$index][self::getIndexPositionForChar($character)] = true;
            }
        }
    }

    private function createStorage(int $numHashers): void
    {
        $this->cache = \SplFixedArray::fromArray(
            \array_fill(0, $numHashers,
                new \SplFixedArray(self::LOWER_CASE_ALPHANUMERIC_KEYSPACE_WIDTH)
            )
        );
    }

    private static function getIndexPositionForChar(string $character): int
    {
        $position = ord($character);

        if ($position <= self::ASCII_NUMERIC_CHARACTER_NUMBER_NINE_POSITION) {
            return (int)$character;
        }

        return $position - self::ASCII_LOWERCASE_CHARACTER_OFFSET;
    }
}