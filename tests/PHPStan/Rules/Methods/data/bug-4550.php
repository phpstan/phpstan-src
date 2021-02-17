<?php

namespace Bug4550;

class Test
{
	/**
	 * @template T
	 * @param class-string<T> $class
	 */
	public static function valuesOf(string $class): void
	{
		(new $class())->values();
		assert(method_exists($class, 'values'));
		$class::values();
	}

	/**
	 * @template T
	 * @param class-string<T> $class
	 */
	public static function doBar(string $class): void
	{
		(new $class())->values();
		$class::values();
	}

	/**
	 * @param class-string<self> $s
	 */
	public function doBaz(string $s): void
	{
		$s::valuesOf(\stdClass::class);
		$s::valuesOf('Person');
	}

	/**
	 * @template T of self
	 * @param class-string<T> $s
	 */
	public function doLorem(string $s): void
	{
		$s::valuesOf(\stdClass::class);
		$s::valuesOf('Person');
	}
}
