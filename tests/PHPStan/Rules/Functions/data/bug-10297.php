<?php declare(strict_types = 1);

namespace Bug10297;

use Generator;
use TypeError;
use UnexpectedValueException;

/**
 * @template K
 * @template T
 * @template L
 * @template U
 *
 * @param iterable<K, T> $stream
 * @param callable(T, K): iterable<L, U> $fn
 *
 * @return Generator<L, U>
 */
function scollect(iterable $stream, callable $fn): Generator
{
    foreach ($stream as $key => $value) {
        yield from $fn($value, $key);
    }
}

/**
 * @template K of array-key
 * @template T
 * @template L of array-key
 * @template U
 *
 * @param array<K, T> $array
 * @param callable(T, K): iterable<L, U> $fn
 *
 * @return array<L, U>
 */
function collectWithKeys(array $array, callable $fn): array
{
    $map = [];
    $counter = 0;

    try {
        foreach (scollect($array, $fn) as $key => $value) {
            $map[$key] = $value;
            ++$counter;
        }
    } catch (TypeError) {
        throw new UnexpectedValueException('The key yielded in the callable is not compatible with the type "array-key".');
    }

    if ($counter !== count($map)) {
        throw new UnexpectedValueException(
            'Data loss occurred because of duplicated keys. Use `collect()` if you do not care about ' .
            'the yielded keys, or use `scollect()` if you need to support duplicated keys (as arrays cannot).',
        );
    }

    return $map;
}

class SomeUnitTest
{
	/**
	 * @return iterable<mixed>
	 */
	public static function someProvider(): iterable
	{
		$unsupportedTypes = [
			// this one does not work:
            'Not a Number' => NAN,
			// these work:
            'Infinity' => INF,
            stdClass::class => new stdClass(),
            self::class => self::class,
            'hello there' => 'hello there',
            'array' => [[42]],
        ];

        yield from collectWithKeys($unsupportedTypes, static function (mixed $value, string $type): iterable {
            $error = sprintf('Some %s error message', $type);

            yield sprintf('"%s" something something', $type) => [$value, [$error, $error, $error]];
        });
	}
}
