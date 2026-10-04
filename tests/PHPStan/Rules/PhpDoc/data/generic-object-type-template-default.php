<?php declare(strict_types = 1);

namespace GenericObjectTypeTemplateDefault;

/**
 * @template TKey
 * @template TValue
 */
class Box
{
}

/**
 * @template T = Box<int, string, bool>
 */
class Holder
{

	/**
	 * @param T $value
	 * @return T
	 */
	public function pass($value)
	{
		return $value;
	}

	/**
	 * @param T $value
	 */
	public function take($value): void
	{
	}

}
