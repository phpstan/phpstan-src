<?php

declare(strict_types = 1);

namespace Bug7423;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template TValue
 */
class ArrayType
{

	/**
	 * @template VKey of array-key
	 * @template V
	 * @param VKey $key
	 * @param V $value
	 * @return self<TKey|VKey, TValue|V>
	 */
	public function add($key, $value): self
	{
		return $this;
	}

}

/** @var ArrayType<string, string> $type */
$type = new ArrayType();

assertType('Bug7423\ArrayType<int|string, int|string>', $type->add(1, 1));
