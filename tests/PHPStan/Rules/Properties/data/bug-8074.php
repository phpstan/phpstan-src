<?php

namespace Bug8074;

use ReflectionClass;
use ReflectionClassConstant;
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
 * @return \Generator<L, U>
 */
function scollect(iterable $stream, callable $fn): \Generator
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
	$values = [];
	$counter = 0;

	foreach (scollect($array, $fn) as $key => $value) {
		try {
			$values[$key] = $value;
		} catch (TypeError $e) {
			throw new UnexpectedValueException('The key yielded in the callable is not compatible with the type "array-key".');
		}

		++$counter;
	}

	if ($counter !== count($values)) {
		throw new UnexpectedValueException(
			'Data loss occurred because of duplicated keys. Use `collect()` if you do not care about ' .
			'the yielded keys, or use `scollect()` if you need to support duplicated keys (as arrays cannot).'
		);
	}

	return $values;
}

function __(string $message, bool $capitalize = true): string
{
	// some fake translation function
	return $capitalize ? ucfirst($message) : $message;
}

final class CsvExport
{
	public const COLUMN_A = 'something';
	public const COLUMN_B = 'else';
	public const COLUMN_C = 'entirely';

	/**
	 * @var array<self::COLUMN_*, string> The translated header as value
	 */
	private static array $headers;

	/**
	 * @return array<self::COLUMN_*, string>
	 */
	public static function getHeaders(): array
	{
		if (!isset(self::$headers)) {
			/** Using [at]var array<self::COLUMN_*, string> $headers here would fix the inspection */
			$headers = collectWithKeys(
				(new ReflectionClass(self::class))->getReflectionConstants(),
				static function (ReflectionClassConstant $constant): iterable {
					/** @var self::COLUMN_* $value */
					$value = $constant->getValue();

					yield $value => __(sprintf('activities.export.urbanus.csv_header.%s', $value), capitalize: false);
				},
			);

			self::$headers = $headers;
		}

		return self::$headers;
	}
}
