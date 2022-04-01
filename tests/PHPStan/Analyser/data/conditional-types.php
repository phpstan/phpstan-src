<?php

namespace ConditionalTypes;

use function PHPStan\Testing\assertType;

abstract class Test
{
	/**
	 * @template TKey of array-key
	 * @template TArray of array<TKey, mixed>
	 *
	 * @param TArray $array
	 *
	 * @return (TArray is non-empty-array ? non-empty-list<TKey> : list<TKey>)
	 */
	abstract public function arrayKeys(array $array);

	/**
	 * @param array $array
	 * @param non-empty-array $nonEmptyArray
	 *
	 * @param array<int, int> $intArray
	 * @param non-empty-array<int, int> $nonEmptyIntArray
	 *
	 * @param array{} $emptyArray
	 */
	public function testArrayKeys(array $array, array $nonEmptyArray, array $intArray, array $nonEmptyIntArray, array $emptyArray): void
	{
		assertType('array<int, (int|string)>', $this->arrayKeys($array));
		assertType('array<int, int>', $this->arrayKeys($intArray));

		assertType('non-empty-array<int, (int|string)>', $this->arrayKeys($nonEmptyArray));
		assertType('non-empty-array<int, int>', $this->arrayKeys($nonEmptyIntArray));

		assertType('array<int, *NEVER*>', $this->arrayKeys($emptyArray));
	}

	/**
	 * @return ($array is non-empty-array ? true : false)
	 */
	abstract public function accessory(array $array): bool;

	/**
	 * @param array $array
	 * @param non-empty-array $nonEmptyArray
	 * @param array{} $emptyArray
	 */
	public function testAccessory(array $array, array $nonEmptyArray, array $emptyArray): void
	{
		assertType('bool', $this->accessory($array));
		assertType('true', $this->accessory($nonEmptyArray));
		assertType('false', $this->accessory($emptyArray));
		assertType('bool', $this->accessory($_GET['array']));
	}

	/**
	 * @return ($as_float is true ? float : string)
	 */
	abstract public function microtime(bool $as_float);

	public function testMicrotime(): void
	{
		assertType('float', $this->microtime(true));
		assertType('string', $this->microtime(false));

		assertType('float|string', $this->microtime($_GET['as_float']));
	}

	/**
	 * @return ($version is 8 ? true : ($version is 10 ? true : false))
	 */
	abstract public function versionIsEightOrTen(int $version);

	public function testVersionIsEightOrTen(): void
	{
		assertType('false', $this->versionIsEightOrTen(6));
		assertType('false', $this->versionIsEightOrTen(7));
		assertType('true', $this->versionIsEightOrTen(8));
		assertType('false', $this->versionIsEightOrTen(9));
		assertType('true', $this->versionIsEightOrTen(10));
		assertType('false', $this->versionIsEightOrTen(11));
		assertType('false', $this->versionIsEightOrTen(12));

		assertType('bool', $this->versionIsEightOrTen($_GET['version']));
	}

	/**
	 * @return ($parameter is true ? int : string)
	 */
	abstract public function missingParameter();

	public function testMissingParameter(): void
	{
		assertType('($parameter is true ? int : string)', $this->missingParameter());
	}

	/**
	 * @return (5 is int ? true : false)
	 */
	abstract public function deterministicReturnValue();

	public function testDeterministicReturnValue(): void
	{
		assertType('true', $this->deterministicReturnValue());
	}

	/**
	 * @param (true is true ? string : bool) $foo
	 * @param (5 is int<4, 6> ? string : bool) $bar
	 * @param (5 is not int<0, 4> ? (4 is bool ? float : string) : bool) $baz
	 */
	public function testDeterministicParameter($foo, $bar, $baz): void
	{
		assertType('string', $foo);
		assertType('string', $bar);
		assertType('string', $baz);
	}

	/**
	 * @template TInt of int
	 * @param TInt $foo
	 * @param (TInt is 5 ? int<0, 10> : int<10, 100>) $bar
	 */
	public function testConditionalInParameter(int $foo, int $bar): void
	{
		assertType('TInt of int (method ConditionalTypes\Test::testConditionalInParameter(), argument)', $foo);
		assertType('(TInt of int (method ConditionalTypes\Test::testConditionalInParameter(), argument) is 5 ? int<0, 10> : int<10, 100>)', $bar);
	}

	/**
	 * @return ($input is null ? null : string)
	 */
	abstract public function retainNullable(?bool $input): ?string;

	public function testRetainNullable(?bool $input): void
	{
		assertType('string|null', $this->retainNullable($input));

		if ($input === null) {
			assertType('null', $this->retainNullable($input));
		} else {
			assertType('string', $this->retainNullable($input));
		}
	}
}
