<?php declare(strict_types=1);

namespace Test;

use Nealio82\BloomFilter\Candidate;
use Nealio82\BloomFilter\CannotUseNonNumericStringAsIntegerException;
use PHPUnit\Framework\TestCase;

final class CandidateTest extends TestCase
{
    public function test_string_as_string(): void
    {
        $string = \sha1((string) \time());

        $candidate = new Candidate($string);

        self::assertSame($string, $candidate->string());
    }

    public function test_integer_as_string(): void
    {
        $timeInt = \time();

        $candidate = new Candidate($timeInt);

        self::assertSame((string) $timeInt, $candidate->string());
    }

    public function test_integer_as_integer(): void
    {
        $timeInt = \time();

        $candidate = new Candidate($timeInt);

        self::assertSame($timeInt, $candidate->integer());
    }

    public function test_integer_string_as_integer(): void
    {
        $timeInt = \time();

        $candidate = new Candidate((string) $timeInt);

        self::assertSame($timeInt, $candidate->integer());
    }

    public function test_alphanumeric_string_as_integer(): void
    {
        $string = \sha1((string) \time());

        $candidate = new Candidate($string);

        $this->expectException(CannotUseNonNumericStringAsIntegerException::class);
        $candidate->integer();
    }
}
