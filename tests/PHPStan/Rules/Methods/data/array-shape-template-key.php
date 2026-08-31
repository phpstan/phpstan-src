<?php declare(strict_types = 1);

namespace ArrayShapeTemplateKeyMethods;

use stdClass;

class Foo
{

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return array{TKey: int}
	 */
	public function stringKey($key)
	{

	}

	/**
	 * @template TKey of stdClass
	 * @param TKey $key
	 * @return array{TKey: int}
	 */
	public function objectKey($key)
	{

	}

	/**
	 * @template TKey of array<string>
	 * @param TKey $key
	 * @return array{TKey: int}
	 */
	public function arrayKey($key)
	{

	}

}

function test(Foo $foo, stdClass $std): void
{
	$foo->stringKey('a');
	$foo->objectKey($std);
	$foo->arrayKey(['a']);
}
