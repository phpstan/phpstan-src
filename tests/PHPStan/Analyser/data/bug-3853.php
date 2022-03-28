<?php

namespace Bug3853;

use function PHPStan\Testing\assertType;

abstract class Test
{
	/**
	 * @template TKey as array-key
	 * @template TArray as array<TKey, mixed>
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
	 */
	public function testArrayKeys(array $array, array $nonEmptyArray, array $intArray, array $nonEmptyIntArray): void
	{
		assertType('array<int, (int|string)>', $this->arrayKeys($array));
		assertType('array<int, int>', $this->arrayKeys($intArray));

		// TODO resolve correctly
		//assertType('non-empty-array<int, (int|string)>', $this->arrayKeys($nonEmptyArray));
		//assertType('non-empty-array<int, int>', $this->arrayKeys($nonEmptyIntArray));

		assertType('array<int, (int|string)>', $this->arrayKeys($nonEmptyArray));
		assertType('array<int, int>', $this->arrayKeys($nonEmptyIntArray));
	}

	/**
	 * @return ($as_float is true ? float : string)
	 */
	abstract public function microtime(bool $as_float = false);

	public function testMicrotime(): void
	{
		// TODO resolve correctly
		//assertType('float', $this->microtime(true));
		//assertType('string', $this->microtime(false));

		assertType('float|string', $this->microtime(true));
		assertType('float|string', $this->microtime(false));
	}
}
