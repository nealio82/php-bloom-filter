## PHP Bloom Filter

This package acts as a configurable [Bloom filter](https://en.wikipedia.org/wiki/Bloom_filter), allowing you to
confidently determine if a particular value has **not** already been seen / cached by your application.

It provides a [**fast and memory-efficient**](https://en.wikipedia.org/wiki/Bloom_filter#Space_and_time_advantages) way
of knowing for certain if a value has _definitely **not** been encountered yet_, but the tradeoff is that there is no
way for knowing with absolute certainty if a value _definitely **has** been encountered_.

So if the Bloom filter says "No, this value has not been processed yet", you can be 100% sure that it hasn't. However,
if the filter says "this value _might_ have been processed", you don't know that with absolute certainty and it would be
best to double-check.

### Using this package

You can use the provided Bloom filters individually if there's a low chance of false positives in your data set, but
a better idea may be to use the `MultiStrategyBloomFilter` with several different hashing algorithms configured (read
the full explanation below for more on why this is the case). Using several different algorithms will increase the
memory usage and reduce the performance of querying the filter, but this should still be a fraction of what it would be
if you weren't using any filters at all.

```php
use Nealio82\BloomFilter\Base64AlphabetBloomFilter;
use Nealio82\BloomFilter\FullAsciiAlphabetBloomFilter;
use Nealio82\BloomFilter\Hasher\Base64StringHasher;
use Nealio82\BloomFilter\Hasher\Md5StringHasher;
use Nealio82\BloomFilter\Hasher\OriginalStringHasher;
use Nealio82\BloomFilter\Hasher\Sha1StringHasher;
use Nealio82\BloomFilter\LowercaseAlphanumericBloomFilter;
use Nealio82\BloomFilter\MultiStrategyBloomFilter;
use Nealio82\BloomFilter\Value;

/*
 * Storing only the raw string using the full ASCII character set as the Bloom filter
 */
$stringFilter = new FullAsciiAlphabetBloomFilter(
    new OriginalStringHasher()
);

\var_dump($stringFilter->definitelyNotInSet(new Value('hello'))); // true

$stringFilter->store(new Value('hello'));

// These three values are all sub-sets of 'hello', and will match if we only compare raw strings
\var_dump($stringFilter->definitelyNotInSet(new Value('hello'))); // false
\var_dump($stringFilter->definitelyNotInSet(new Value('hell'))); // false
\var_dump($stringFilter->definitelyNotInSet(new Value('he'))); // false

// This super-set of 'hello' will not match, as we haven't stored the following characters in the filter: `, wrd`
\var_dump($stringFilter->definitelyNotInSet(new Value('hello, world'))); // true

/*
 * Chaining multiple Bloom filters to reduce the chance of false positives
 */
$stringFilter = new MultiStrategyBloomFilter(
    new FullAsciiAlphabetBloomFilter(
        new OriginalStringHasher()
    ),
    new Base64AlphabetBloomFilter(
        new Base64StringHasher()
    ),
    new LowercaseAlphanumericBloomFilter(
        new Md5StringHasher()
    ),
    new LowercaseAlphanumericBloomFilter(
        new Sha1StringHasher()
    ),
);

\var_dump($stringFilter->definitelyNotInSet(new Value('hello'))); // true

$stringFilter->store(new Value('hello'));

\var_dump($stringFilter->definitelyNotInSet(new Value('hello'))); // false

// The sub-sets of 'hello' have been hashed against different algorithms, and the number of false matches has reduced
\var_dump($stringFilter->definitelyNotInSet(new Value('hell'))); // true
\var_dump($stringFilter->definitelyNotInSet(new Value('he'))); // true

// This super-set of 'hello' will not match, as we haven't stored the following characters in any filters: `, wrd`
\var_dump($stringFilter->definitelyNotInSet(new Value('hello, world'))); // true
```

## More about Bloom filters

### An example

Imagine you have a CSV file containing millions of records, which you need to process and cross-reference against
several different data sources via a flaky network connection. Let's assume that query batching strategies aren't
applicable here for whatever reason; perhaps dataset B is exposed via a 3rd-party rest API which only allows you to
fetch single items by ID. In this example we'll assume that there isn't a 1:1 mapping between your CSV and the 3rd-party
API, meaning that every row being processed in your CSV in could potentially end up making the very same API request
that processing the previous row made.

In order to reduce wasted time waiting for responses over your network, you want to find a way of avoiding making the
lookup calls for information that you've already fetched. Or, perhaps in this example, this 3rd party has a 'pay per
lookup' model and you're going to bankrupt yourself by repeating the same query over-and-over, which means that
distributing the job across asynchronous workers won't help you here. Either way, **the goal is to only fetch the
information if we know we haven't already requested it**.

You might be thinking "Aha! I'll just cache **all** results in an array and use the request URL / params as the array
key!". Well, as your data sets grow so will your memory usage, and you're going to run into the classic 'failed
allocating .... bytes' failure mode sooner or later.

You could try storing all the results in a more memory-efficient structure, such as
a [linked list](https://www.php.net/manual/en/class.spldoublylinkedlist.php), but these structures are slow to search
through and doing it for millions of records is not going to be a quick task.

So you're seemingly left with the choice of making a slow search through a linked list for each of millions of records
on each iteration regardless of if you've already got the data or not, or making a slow and expensive network call every
time instead.

You need a way of knowing if a particular record has already been queried *before* you start going into your linked
lists looking for it. Remember, we'd be doing this iteration through the list for *every* row in the CSV. Unless you can
avoid unnecessary trips through the list, it's going to take ages to process your CSV...

A Bloom filter helps here because you can immediately know if you **haven't** already fetched the data and move
straight to the 'fetch info over the network' step, thus avoiding the slow iterations through a growing linked list.

#### Diagram

```
                  +---------------------+
                  | Is it in the cache? |
                  +---------------------+
                            |
                            v
                  +--------------------+
                  | Bloom filter check |
                  +---------+----------+
                            |
             +--------------+-------------+
             |                            | 
             v                            v
+--------------------------+       +-------------------+
| Definitely not in filter |       | Possibly in cache | 
+------------+-------------+       +---------+---------+
             |                               |
      we can bypass the                      v
    entire cache check flow     +-------------------------+
             |                  | Linked list cache check |
             |                  +------------+------------+
             |                               |
             |                               |
             |             linked lists are highly efficient with
             |             memory usage, but slow to search through
             |                               |
             |                               |
             |                   +-----------+-----------+
             |                   |                       |
             |            we should avoid                |                 
             |          this path if we can              |
             |                   |                       |
             |                   v                       v
             |        +--------------------+     +----------------+
             |        | Not found in cache |     | Found in cache |
             |        +---------+----------+     +-------+--------+
             |                  |                        |
             |                  |                 avoid unnecessary
             |                  |                 network requests!
             |                  v                        |
             |     +-------------------------------+     |
             +---->| Make network request          |     |
                   | Add data to linked list cache |     |
                   | Add record to Bloom filter    |     |
                   +-------------------+-----------+     |
                                       |                 |
                                       v                 v
                                 +--------------------------------+
                                 | Return the value to the client |
                                 +--------------------------------+
```

#### Other caching improvements to use alongside a Bloom filter

There are additional caching strategies you could employ to make the lookup through the linked list faster, although
these are beyond the scope of this package.

One particular strategy is having an array of linked lists, where the array key is some representation of part of the
hash of the record you're storing. Instead of one huge linked list containing all records in the cache, you would end up
with several much smaller linked lists which you can jump directly to and so split the search into smaller chunks.

Eg, if you needed memory-efficient storage of records inside linked lists, and record IDs are UUIDs, you could arrange
an array of linked lists like so:

```php
/**
 * Given this small sample of a set of millions of record IDs:
 * 46a6e6df-9ad5-4d05-a7a3-e2d06582678d
 * a7582da0-bee2-42fd-91b5-127dbd1c1428
 * d450bf2f-0c41-417c-b46d-d35a0d124d0e
 * 51a9cacc-1ece-4313-88c3-4dca92dc9a05
 * a7582da0-9ad5-4d05-a7a3-e2d06582678d
 * a7582da0-bee2-42fd-91b5-e2d06582678d
 * d450bf2f-1ece-4313-88c3-4dca92dc9a05
 *
 * We can use the first section of the UUID to distribute 
 * the IDs across the buckets of linked list caches.
 */

$bucketList = [
 '46a6e6df' => new \SplDoublyLinkedList(), // contains '46a6e6df-9ad5-4d05-a7a3-e2d06582678d'
 'a7582da0' => new \SplDoublyLinkedList(), // contains 'a7582da0-bee2-42fd-91b5-127dbd1c1428', 'a7582da0-9ad5-4d05-a7a3-e2d06582678d', and 'a7582da0-bee2-42fd-91b5-e2d06582678d'
 'd450bf2f' => new \SplDoublyLinkedList(), // contains 'd450bf2f-0c41-417c-b46d-d35a0d124d0e' and 'd450bf2f-1ece-4313-88c3-4dca92dc9a05'
 '51a9cacc' => new \SplDoublyLinkedList(), // contains '51a9cacc-1ece-4313-88c3-4dca92dc9a05'
];

/** 
 * Now, when we need to check the cache for a particular ID,
 * such as 'a7582da0-9ad5-4d05-a7a3-e2d06582678d'
 * instead of searching one long list for the existence of a record,
 * we know immediately to jump to the a7582da0 bucket and search 
 * inside there instead. 
 * 
 * This strategy uses more memory than a single linked list, 
 * but increases performance of searching, especially with very
 * large data sets  
 */
```

### Other example use-cases for Bloom filters

* Web crawlers (e.g. Googlebot) can use Bloom filters to know if they've already crawled a domain / page so that they
  can avoid never-ending crawls when they encounter circular references between pages. At the scale of billions of pages
  on the internet, this is a far better strategy than keeping a list of every individual crawl.
* Url shortening services (e.g. TinyUrl or Bit.ly) can keep a Bloom filter of blacklisted sites / domains, and
  confidently forward users to the destination URL if the requested page is "definitely not in the blacklist"

### How Bloom filters work

This is easiest to understand from the example of strings, and then to look at the case for integers later.

Imagine you're working on an application which needs to process the words in a dictionary, and your first line gives you
a string containing the characters `test`. Your Bloom filter is set up as an array where the keys are the letters `a`
to `z`, and the values are all set to `false` (or `0` in the example below).

Eg:

```
[a][b][c][d][e][f][g][h][i][j][k][l][m][n][o][p][q][r][s][t][u][v][w][x][y][z] <-- keys
[0][0][0][0][0][0][0][0][0][0][0][0][0][0][0][0][0][0][0][0][0][0][0][0][0][0] <-- values
```

As you process the string you also update the Bloom filter to store the word `test`, which the filter does by setting
the array elements for each unique letter in the word to `true` (or `1`). This means our filter will set `t`, `e`,
and `s` to `1` in this example.

```
[a][b][c][d][e][f][g][h][i][j][k][l][m][n][o][p][q][r][s][t][u][v][w][x][y][z] <-- keys
[0][0][0][0][1][0][0][0][0][0][0][0][0][0][0][0][0][0][1][1][0][0][0][0][0][0] <-- values
```

You then move to processing the next word, which is `tested`. You want to check if you've already seen this word, so you
ask the Bloom filter if the string `tested` definitely does not exist in the stored set.

The Bloom filter checks the values held in the array at the positions of  `t`, `e`, `s`, `d`.

```
[a][b][c][d][e]...[s][t][u][v][w][x][y][z] <-- keys
[0][0][0][0][1]...[1][1][0][0][0][0][0][0] <-- values
```

We can see that the letters `t`, `e`, and `s` have already been encountered from the word `test`, but `d` is
still `false` so we know for certain that we haven't (yet) seen any words containing a `d`, including `tested`.

We process the word `tested` and also ask the Bloom filter to store the word, which it does by setting `d` to `true` in
its array. We keep flipping bits from `false` to `true` as we process our dictionary file and encounter new letters.

Imagine we've now moved onto the third word, which is `sett` (the name of the den where badgers live). The Bloom filter
checks the values held in the array at the positions of  `s`, `e`, `t`.

```
[a][b][c][d][e]...[s][t][u][v][w][x][y][z] <-- keys
[0][0][0][1][1]...[1][1][0][0][0][0][0][0] <-- values
```

Now we have a different result. The values for `s`, `e`, and `t` are all `true`. This means we *might* have already
processed the word `sett`, but we can never be sure. We would then need to take some sort of action to make sure it
isn't a false positive.

The fourth word is `untested`. Because `u` and `n` are still `false` in the filter, we know we haven't seen this word
yet. The same goes for `hello` and `world`. If any values are `false` for any letters in the word (even if all the other
letters have all been flipped to `true`), we know for sure it's a new value to us.

### Reducing false positives (converting "maybes" to "definitely nots")

#### More filters

As you saw in the third example word (`sett` above) when a value being checked against the filter is a sub-set of the
characters of words which have already been checked, we can end up with false positives. We can reduce the ratio of
these by representing the same data in different ways, and setting up a distinct Bloom filter for each of those.

Eg, we can mitigate the `test` / `sett` false positive above by hashing the words against different algorithms and
comparing each:

```
$test = 'test';
$sett = 'sett';

var_dump(md5($test));
var_dump(sha1($test));
var_dump(base64_encode($test));

string(32) "098f6bcd4621d373cade4e832627b4f6" <- md5('test') contains no '5'
string(40) "a94a8fe5ccb19ba61c4c0873d391e987982fbbd3"
string(8) "dGVzdA==" <- contains no 'c', '2', or '0'


var_dump(md5($sett));
var_dump(sha1($sett));
var_dump(base64_encode($sett));

string(32) "ed72b39fed0414c29c4ce07065384d9d"
string(40) "022fe9e6785e0efbfadd413638a0159ae2113736"
string(8) "c2V0dA=="
```

If we were to set up three Bloom filters for each of raw-string, MD5, SHA1, and Base64, then we would see the following:

* The raw-string Bloom filter would say `sett` is _possibly_ contained within the filter which already contains `test`,
  as `t`, `e`, and `s` are all `true`.
* The MD5 Bloom filter would say that `sett` is _definitely not_ contained within its filter, as the MD5 representation
  of `sett` contains a `5`, and `md5('test')` doesn't.
* The SHA1 Bloom filter would say that `sett`  is _possibly_ contained within the filter, as all the letters
  returned by `sha1('sett')` are already present in the output from `sha1('test')`.
* The Base64 Bloom filter would say that `sett` is _definitely not_ contained within its filter, as the
  characters `c`, `2`, and `0` do not exist in the output from `base64_encode('test')`.

We could ask each of these filters in turn if they can confidently say that `sett` is not within its filter. If **any**
of them confidently report that the word has not been stored in the filter, then we know for certain that it doesn't
exist in any of the filters.

#### Wider key-space

Along with more filters, we can tune the algorithm by adding a wider key-space that our values can be converted to. For
example, if we had a filter containing space for only `[a][b][c][d][e][f]` and a hashing algorithm which always
converted all inputs to one of the letters `a` to `f`, we would be severely limited by the key-space of 6 values that we
could store in our filter.

The [ASCII character set has a key-space width of `128`](https://cs.smu.ca/~porter/csc/ref/ascii.html) (`0` to `127`),
so a hashing algorithm which can make use of the `=`, `~`, `DEL`, `SPACE`, etc characters would also reduce the risk of
false positives.

#### More entropy

Some Bloom
filters [hash the same data several times over](https://en.wikipedia.org/wiki/Bloom_filter#Optimal_number_of_hash_functions).
This could also be an effective strategy for diversifying the hashes that you check in your filter. You are limited only
by the set of unique characters you can store in your key space (and how effectively the hashing algorithm distributes
values across them), so have a go at adding your own. You could create a hashing algorithm which maps words to sets of
Emojis, and you would then compare smilies against poops.

### Checking integers

The filters above are fast and memory-efficient methods for filtering string values, but we can optimise memory usage
even further for integers.

For example, assuming that 1 Byte of memory is needed for each ASCII character we use in our filter, using a key space
of all uppercase and lowercase letters, plus numbers 0-9 means we're using `(2 * 26) + 10 = 62 Bytes` of memory on a
single filter.

For checking integers we can use a fraction of that while representing a much greater set of values by converting
numbers to their binary representations and then storing the filter as a single integer. On 32-bit systems this will
take 4 Bytes (representing up to about `2 billion` values), and on 64-bit systems this will use 8
Bytes (`9 billion billion` values).

We begin with a filter with the internal integer set to `0`.
[Represented as a single Byte, this is `00000000`](https://www.oreilly.com/library/view/cisco-ccentccna-icnd1/9780133367843/app01.html).

When we want to store the number `1`, the internal integer's Byte representation becomes `00000001`.

Now, when we check if the number `2` (`00000010`) is in the filter, we can see that there is a mismatch where the
number `2` has a `1` where a `0` exists in the filter. We can say that `2` definitely does not exist in the filter.

Storing `2` into the filter means just setting the filter's bits to `1` wherever they aren't already `1`. Eg, the filter
becomes `00000011`. This means that `1` and `2` are contained within the filter, and the filter's internal integer
simply stores the value `3`.

However, this means that the number `3` (whose binary representation is `0000011`) will return a false positive if we
ask the filter for its existence.

If we then store the number `100` (`01100100`), our filter becomes `0000011` augmented with `01100100` => `01100111`.

Any numbers within the range `01101111` (`111`) to `01111111` (`127`), and `10000000` (`128`) and above will be
**definitely not in set**.

It's most efficient when using an integer-based Bloom filter like this one to process results in ascending order if you
can, as the left-most bit will always be the last to flip to `true`.

## Caveats of Bloom filters

1. While you can always be sure that the filter is 100% correct when it says something definitely is not contained
   within the filter, the opposite is not true. That means that you cannot ever be certain that a particular value
   definitely _does_ exist within the filter.
2. As the filter begins to fill up with values, the rate of false positives also increases. A fully saturated filter (
   where all the bits have been flipped to `1`) will be just as effective as having no filter at all. However, even once
   the filter is completely saturated you would not see a noticeable performance penalty, and the time savings you made
   while the filter was filling up from the beginning would probably be a huge benefit.
3. It's an additional layer of caching and complexity in your system.