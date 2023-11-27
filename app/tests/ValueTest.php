<?php declare(strict_types=1);

namespace Test;

use Nealio82\BloomFilter\CannotUseNonNumericStringAsIntegerException;
use Nealio82\BloomFilter\Value;
use PHPUnit\Framework\TestCase;

final class ValueTest extends TestCase
{
    public function test_string_as_string(): void
    {
        $string = \sha1((string) \time());

        $value = new Value($string);

        self::assertSame($string, $value->string());
    }

    public function test_integer_as_string(): void
    {
        $timeInt = \time();

        $value = new Value($timeInt);

        self::assertSame((string) $timeInt, $value->string());
    }

    public function test_integer_as_integer(): void
    {
        $timeInt = \time();

        $value = new Value($timeInt);

        self::assertSame($timeInt, $value->integer());
    }

    public function test_integer_string_as_integer(): void
    {
        $timeInt = \time();

        $value = new Value((string) $timeInt);

        self::assertSame($timeInt, $value->integer());
    }

    public function test_alphanumeric_string_as_integer(): void
    {
        $string = \sha1((string) \time());

        $value = new Value($string);

        $this->expectException(CannotUseNonNumericStringAsIntegerException::class);
        $value->integer();
    }
}
