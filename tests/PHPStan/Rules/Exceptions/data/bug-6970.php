<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug6970;

use ArrayAccess;
use Generator;
use TypeError;
use UnexpectedValueException;
use function count;

/**
 * @template K
 * @template T
 * @template L
 * @template U
 *
 * @param iterable<K, T> $stream
 * @param callable(T, K): Generator<L, U> $fn
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
 * @param mixed[] $array
 *
 * @return mixed[]
 */
function collectWithKeys(array $array, callable $fn): array
{
	$stream = scollect($array, $fn);

	$values = [];
	$counter = 0;

	foreach ($stream as $key => $value) {
		try {
			$values[$key] = $value;
		} catch (TypeError) {
			throw new UnexpectedValueException('The key yielded in the callable is not compatible with the type "array-key".');
		}

		++$counter;
	}

	if ($counter !== count($values)) {
		throw new UnexpectedValueException(
			'Data loss occurred because of duplicated keys. Use `collect()` if you do not care about ' .
			'the yielded keys, or use `scollect()` if you need to support duplicated keys (as arrays cannot).',
		);
	}

	return $values;
}

class MoreCases
{

	/**
	 * @param mixed[] $array
	 * @param mixed $key
	 */
	public function writeMixedKey(array $array, $key): void
	{
		try {
			$array[$key] = 1;
		} catch (TypeError) { // not dead
			echo 'caught';
		}
	}

	/**
	 * @param mixed[] $array
	 */
	public function writeObjectKey(array $array, \stdClass $key): void
	{
		try {
			$array[$key] = 1;
		} catch (TypeError) { // not dead
			echo 'caught';
		}
	}

	/**
	 * @param mixed[] $array
	 */
	public function writeIntKey(array $array, int $key): void
	{
		try {
			$array[$key] = 1;
		} catch (TypeError) { // error: Dead catch - TypeError is never thrown in the try block.
			echo 'caught';
		}
	}

	/**
	 * @param mixed[] $array
	 * @param mixed $value
	 */
	public function append(array $array, $value): void
	{
		try {
			$array[] = $value;
		} catch (TypeError) { // error: Dead catch - TypeError is never thrown in the try block.
			echo 'caught';
		}
	}

	/**
	 * @param mixed[] $array
	 * @param mixed $key
	 */
	public function assignOpMixedKey(array $array, $key): void
	{
		try {
			$array[$key] .= 'x';
		} catch (TypeError) { // not dead
			echo 'caught';
		}
	}

	/**
	 * @param mixed[] $array
	 * @param mixed $key
	 */
	public function readMixedKey(array $array, $key): void
	{
		try {
			$x = $array[$key];
		} catch (TypeError) { // not dead
			echo 'caught';
		}
	}

	/**
	 * @param mixed[] $array
	 */
	public function readIntKey(array $array, int $key): void
	{
		try {
			$x = $array[$key];
		} catch (TypeError) { // error: Dead catch - TypeError is never thrown in the try block.
			echo 'caught';
		}
	}

	/**
	 * @param mixed[] $array
	 * @param mixed $key
	 */
	public function readCoalesceMixedKey(array $array, $key): void
	{
		try {
			$x = $array[$key] ?? null;
		} catch (TypeError) { // not dead
			echo 'caught';
		}
	}

	/**
	 * @param mixed[] $array
	 * @param mixed $key
	 */
	public function issetMixedKey(array $array, $key): void
	{
		try {
			$x = isset($array[$key]);
		} catch (TypeError) { // not dead
			echo 'caught';
		}
	}

	/**
	 * @param mixed[] $array
	 * @param mixed $key
	 */
	public function unsetMixedKey(array $array, $key): void
	{
		try {
			unset($array[$key]);
		} catch (TypeError) { // not dead
			echo 'caught';
		}
	}

	/**
	 * @param mixed[] $array
	 */
	public function unsetIntKey(array $array, int $key): void
	{
		try {
			unset($array[$key]);
		} catch (TypeError) { // error: Dead catch - TypeError is never thrown in the try block.
			echo 'caught';
		}
	}

	/**
	 * @param mixed $key
	 */
	public function stringOffsetWriteMixedKey(string $s, $key): void
	{
		try {
			$s[$key] = 'x';
		} catch (TypeError) { // not dead
			echo 'caught';
		}
	}

	/**
	 * @param mixed $key
	 */
	public function stringOffsetReadMixedKey(string $s, $key): void
	{
		try {
			$x = $s[$key];
		} catch (TypeError) { // not dead
			echo 'caught';
		}
	}

	/**
	 * @param mixed[] $array
	 * @param int|mixed[] $key
	 */
	public function writeArrayOrIntKey(array $array, $key): void
	{
		try {
			$array[$key] = 1;
		} catch (TypeError) { // not dead - key may be an array
			echo 'caught';
		}
	}

	/**
	 * @param mixed[] $array
	 * @param int|\stdClass $key
	 */
	public function writeObjectOrIntKey(array $array, $key): void
	{
		try {
			$array[$key] = 1;
		} catch (TypeError) { // not dead - key may be an object
			echo 'caught';
		}
	}

	/**
	 * @param mixed[]|int $arrayOrInt
	 * @param mixed $key
	 */
	public function readArrayOrIntVar($arrayOrInt, $key): void
	{
		try {
			$x = $arrayOrInt[$key];
		} catch (TypeError) { // not dead - reading an array offset with an illegal key throws
			echo 'caught';
		}
	}

	/**
	 * @param string|int $stringOrInt
	 * @param mixed $key
	 */
	public function readStringOrIntVar($stringOrInt, $key): void
	{
		try {
			$x = $stringOrInt[$key];
		} catch (TypeError) { // not dead - reading a string offset with an illegal key throws
			echo 'caught';
		}
	}

	/**
	 * @param mixed $key
	 */
	public function writeConcreteArrayAccessKey(ConcreteArrayAccess $container, $key): void
	{
		try {
			$container[$key] = 1;
		} catch (TypeError) { // error: dead - ArrayAccess offsetSet() handles the offset, so no TypeError from the illegal offset
			echo 'caught';
		}
	}

	/**
	 * @param mixed[]|ConcreteArrayAccess $container
	 * @param mixed $key
	 */
	public function writeMaybeArrayAccessKey($container, $key): void
	{
		try {
			$container[$key] = 1;
		} catch (TypeError) { // not dead - container may be a plain array, whose illegal offset throws
			echo 'caught';
		}
	}

}

/**
 * @implements ArrayAccess<mixed, mixed>
 */
final class ConcreteArrayAccess implements ArrayAccess
{

	public function offsetExists($offset): bool
	{
		return true;
	}

	public function offsetGet($offset): mixed
	{
		return null;
	}

	/**
	 * @throws \RuntimeException
	 */
	public function offsetSet($offset, $value): void
	{
		throw new \RuntimeException();
	}

	public function offsetUnset($offset): void
	{
	}

}
