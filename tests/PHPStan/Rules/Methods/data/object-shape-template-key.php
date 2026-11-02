<?php declare(strict_types = 1);

namespace ObjectShapeTemplateKeyMethods;

use stdClass;

class Foo
{

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return object{TKey: int}
	 */
	public function stringKey($key)
	{

	}

	/**
	 * @template TKey of stdClass
	 * @param TKey $key
	 * @return object{TKey: int}
	 */
	public function objectKey($key)
	{

	}

	/**
	 * @template TKey of int
	 * @param TKey $key
	 * @return object{TKey: int}
	 */
	public function intKey($key)
	{

	}

}

function test(Foo $foo, stdClass $std): void
{
	$foo->stringKey('a');
	$foo->objectKey($std);
	$foo->intKey(1);
}
