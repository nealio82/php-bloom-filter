<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

final class FullAsciiAlphabetBloomFilter extends BloomFilter
{
    private const ASCII_FULL_CHARACTER_SET_KEYSPACE_WIDTH = 128;

    private \SplFixedArray $cache;

    public function __construct(
        private readonly StringHasher $hasher
    ) {
        $this->cache = new \SplFixedArray(self::ASCII_FULL_CHARACTER_SET_KEYSPACE_WIDTH);
    }

    protected function candidateDefinitelyDoesNotExistInStorage(Value $value): bool
    {
        $hash = $this->hasher->hash($value);

        foreach (\str_split($hash) as $character) {
            if ($this->cache[\ord($character)] !== true) {
                return true;
            }
        }

        return false;
    }

    protected function addItemToStorage(Value $value): void
    {
        $hash = $this->hasher->hash($value);

        foreach (\str_split($hash) as $character) {
            if (\ord($character) > 127) {
                throw new UnsupportedCharacterException(
                    \sprintf(
                        'The provided hashing algorithm produced a hash (%s) containing disallowed characters',
                        $hash
                    )
                );
            }
            $this->cache[\ord($character)] = true;
        }
    }
}
