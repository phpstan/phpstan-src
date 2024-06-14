<?php

namespace Bug6399;

use function PHPStan\Testing\assertType;

class AsyncTask{
	/**
	 * @phpstan-var \ArrayObject<int, array<string, mixed>>|null
	 */
	private static $threadLocalStorage = null;

	final public function __destruct(){
		assertType('ArrayObject<int, array<string, mixed>>|null', self::$threadLocalStorage);
		if(self::$threadLocalStorage !== null){
			assertType('ArrayObject<int, array<string, mixed>>', self::$threadLocalStorage);
			if (isset(self::$threadLocalStorage[$h = spl_object_id($this)])) {
				assertType('ArrayObject<int, array<string, mixed>>', self::$threadLocalStorage);
				unset(self::$threadLocalStorage[$h]);
				assertType('ArrayObject<int, array<string, mixed>>', self::$threadLocalStorage);
				if(self::$threadLocalStorage->count() === 0){
					self::$threadLocalStorage = null;
				}
			}
		}
	}

	public function doFoo(): void
	{
		if(self::$threadLocalStorage === null) {
			return;
		}

		assertType('ArrayObject<int, array<string, mixed>>', self::$threadLocalStorage);
		if (isset(self::$threadLocalStorage[1])) {
			assertType('ArrayObject<int, array<string, mixed>>&hasOffset(1)', self::$threadLocalStorage);
		} else {
			assertType('ArrayObject<int, array<string, mixed>>', self::$threadLocalStorage);
		}

		assertType('ArrayObject<int, array<string, mixed>>', self::$threadLocalStorage);
		if (isset(self::$threadLocalStorage[1]) && isset(self::$threadLocalStorage[2])) {
			assertType('ArrayObject<int, array<string, mixed>>&hasOffset(1)&hasOffset(2)', self::$threadLocalStorage);
			unset(self::$threadLocalStorage[2]);
			assertType('ArrayObject<int, array<string, mixed>>&hasOffset(1)', self::$threadLocalStorage);
		}
	}

	/**
	 * @param non-empty-array $a
	 * @return void
	 */
	public function doBar(array $a): void
	{
		assertType('non-empty-array', $a);
		unset($a[1]);
		assertType('array<mixed~1, mixed>', $a);
	}

}
