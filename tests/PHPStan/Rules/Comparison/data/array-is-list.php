<?php

namespace ArrayIsListImpossible;

class Foo
{

	/**
	 * @param array<string, int> $stringKeyedArray
	 */
	public function doFoo(array $stringKeyedArray)
	{
		if (array_is_list($stringKeyedArray)) {

		}
	}

	/**
	 * @param array<int> $mixedArray
	 */
	public function doBar(array $mixedArray)
	{
		if (array_is_list($mixedArray)) {
			// Fine
		}
	}

	/**
	 * @param array<array-key, int> $arrayKeyedInts
	 */
	public function doBaz(array $arrayKeyedInts)
	{
		if (array_is_list($arrayKeyedInts)) {
			// Fine
		}
	}

	public function doBax()
	{
		if (array_is_list(['foo' => 'bar', 'bar' => 'baz'])) {

		}

		if (array_is_list(['foo', 'foo' => 'bar', 'bar' => 'baz'])) {
			// Fine
		}
	}
}
