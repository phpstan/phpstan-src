<?php declare(strict_types = 1);

namespace ObjectShapeTemplateKeyPhpDoc;

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
	 * @template TKey of \stdClass
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

	/**
	 * @template TKey
	 * @param TKey $key
	 * @return object{TKey: int}
	 */
	public function mixedKey($key)
	{

	}

}
