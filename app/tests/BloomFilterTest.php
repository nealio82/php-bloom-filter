<?php declare(strict_types=1);

namespace Test;

use Test\Doubles\MismatchedStringHasher;
use Test\Doubles\StringHasherSpy;
use Test\Doubles\FixedStringHasher;
use Nealio82\BloomFilter\BloomFilter;
use PHPUnit\Framework\TestCase;
use Test\Doubles\NonHashingStringHasher;

final class BloomFilterTest extends TestCase
{
    public function test_empty_filter(): void
    {
        $filter = new BloomFilter(new FixedStringHasher('bbb'));

        $this->assertTrue(
            $filter->definitelyNotInSet('aaa')
        );
    }

    public function test_different_items(): void
    {
        $filter = new BloomFilter(new NonHashingStringHasher());

        $this->assertTrue(
            $filter->definitelyNotInSet('bbb')
        );
    }

    public function test_item_is_in_storage(): void
    {
        $filter = new BloomFilter(new NonHashingStringHasher());

        $filter->store('aaa');

        $this->assertFalse(
            $filter->definitelyNotInSet('aaa')
        );
    }

    public function test_numeric_item_in_storage(): void
    {
        $filter = new BloomFilter(new NonHashingStringHasher());

        $filter->store('0123456789');

        $this->assertFalse(
            $filter->definitelyNotInSet('0123456789')
        );
    }

    public function test_numeric_may_be_storage(): void
    {
        $filter = new BloomFilter(new NonHashingStringHasher());

        $filter->store('0123456789');

        $this->assertFalse(
            $filter->definitelyNotInSet('123')
        );
    }

    public function test_numeric_item_not_in_storage(): void
    {
        $filter = new BloomFilter(new NonHashingStringHasher());

        $filter->store('0123456789');

        $this->assertTrue(
            $filter->definitelyNotInSet('aaa')
        );
    }

    public function test_similar_item_might_be_in_set(): void
    {
        $filter = new BloomFilter(new FixedStringHasher('test'));

        $filter->store('test');

        $this->assertFalse(
            $filter->definitelyNotInSet('tset')
        );
    }

    public function test_full_alphanumeric_range_not_in_set(): void
    {
        $filter = new BloomFilter(new NonHashingStringHasher());

        $word = 'abcdefghijklmnopqrstuvwxyz0123456789';

        $filter->store('aaa');

        $this->assertTrue(
            $filter->definitelyNotInSet($word)
        );
    }

    public function test_full_alphanumeric_range_maybe_in_set(): void
    {
        $filter = new BloomFilter(new NonHashingStringHasher());

        $word = 'abcdefghijklmnopqrstuvwxyz0123456789';

        $filter->store($word);

        $this->assertFalse(
            $filter->definitelyNotInSet(\strrev($word))
        );
    }

    public function test_alphanumeric_range_subset_maybe_in_set(): void
    {
        $filter = new BloomFilter(new NonHashingStringHasher());

        $word = 'abcdefghijklmnopqrstuvwxyz0123456789';

        $filter->store($word);

        $this->assertFalse(
            $filter->definitelyNotInSet('hello1234')
        );
    }

    public function test_not_in_set_when_any_character_not_found(): void
    {
        $filter = new BloomFilter(new MismatchedStringHasher('test', 'testy'));

        $filter->store('test');

        $this->assertTrue(
            $filter->definitelyNotInSet('testy')
        );
    }

    public function test_not_in_set_when_no_hashers_find_item(): void
    {
        $filter = new BloomFilter(
            new NonHashingStringHasher(),
            new NonHashingStringHasher(),
        );

        $filter->store('test');

        $this->assertTrue(
            $filter->definitelyNotInSet('bar')
        );
    }

    public function test_return_after_first_found_item(): void
    {
        $this->markTestSkipped('Need to separate the hasher from the storage!');
        $spy1 = new StringHasherSpy(new FixedStringHasher('foo'));
        $spy2 = new StringHasherSpy(new FixedStringHasher('bar'));

        $filter = new BloomFilter($spy1, $spy2);

        $filter->store('foo');

        $this->assertFalse(
            $filter->definitelyNotInSet('foo')
        );

        $this->assertTrue($spy1->wasCalled());
        $this->assertFalse($spy2->wasCalled());
    }
}