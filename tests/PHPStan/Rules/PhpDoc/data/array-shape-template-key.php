<?php declare(strict_types = 1);

namespace ArrayShapeTemplateKeyPhpDoc;

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
	 * @template TKey of \stdClass
	 * @param TKey $key
	 * @return array{TKey: int}
	 */
	public function objectKey($key)
	{

	}

	/**
	 * @template TKey of \stdClass
	 * @param TKey $key
	 * @return array{a: int, ...<TKey, bool>}
	 */
	public function objectUnsealedKey($key)
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

	/**
	 * @template TKey
	 * @param TKey $key
	 * @return array{TKey: int}
	 */
	public function mixedKey($key)
	{

	}

}
