<?php declare(strict_types = 1);

namespace Bug12755;

class MyEnum
{
	public const ONE = 'one';
	public const TWO = 'two';
	public const THREE = 'three';
}

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

	/**
	 * @param array{
	 *     key1: ?bool,
	 *     key2: null,
	 * } $myArray
	 */
	public function testNull(array $myArray): ?\stdClass
	{
	    if (\in_array(null, $myArray, true)) {
	        return null;
	    }

		return (object) $myArray;
	}

    /**
     * @param list<MyEnum::*> $stack
     */
    public function testEnum(array $stack): bool
    {
        return count($stack) === 1 && in_array(MyEnum::ONE, $stack, true);
    }

	/**
	 * @param array{1|2|3} $stack
	 * @param array{1|2|3, 1|2|3} $stack2
	 * @param array{1|2|3, 2|3} $stack3
	 * @param array{a?: 1, b: 2|3} $stack4
	 * @param array{a?: 1} $stack5
	 */
	public function sayHello(array $stack, array $stack2, array $stack3, array $stack4, array $stack5): void
	{
		if (in_array(1, $stack, true)) {
		}

		if (in_array(1, $stack2, true)) {
		}

		if (in_array(1, $stack3, true)) {
		}

		if (in_array(1, $stack4, true)) {
		}
		
		if (in_array(1, $stack5, true)) {
		}
	}
}
