<?php declare(strict_types=1);

namespace Nealio82\BloomFilter;

final readonly class Candidate
{
    public function __construct(
        private int|string $value
    ) {
    }

    public function string(): string
    {
        return (string) $this->value;
    }

    public function integer(): int
    {
        if (! \is_numeric($this->value)) {
            throw new CannotUseNonNumericStringAsIntegerException(
                \sprintf(
                    'The string "%s" contains characters not contained within the set 0-9',
                    $this->value
                )
            );
        }

        return (int) $this->value;
    }
}
