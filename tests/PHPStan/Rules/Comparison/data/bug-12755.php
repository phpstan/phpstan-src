<?php declare(strict_types = 1);

namespace Bug12755;

class HelloWorld
{
	/**
	 * @param array{
	 *     key1: ?int,
	 *     key2: ?string,
	 * } $myArray
	 */
	public function testOther(array $myArray): ?\stdClass
	{
		if (\in_array(null, $myArray, true)) {
			return null;
		}

		return (object) $myArray;
	}

	/**
	 * @param array{
	 *     key1: ?bool,
	 * } $myArray
	 */
	public function testBool(array $myArray): ?\stdClass
	{
		if (\in_array(null, $myArray, true)) {
			return null;
		}

		return (object) $myArray;
	}
}
