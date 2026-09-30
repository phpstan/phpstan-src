<?php // lint >= 8.1

namespace ClassConstantWildcardUnresolvable;

interface Extension
{

	/**
	 * @param static::* $type
	 */
	public function doFoo(string $type): void;

	/**
	 * @param static::VERSION $type
	 */
	public function doBar(string $type): void;

}

class NotFinal
{

	/**
	 * @param static::NOPE_* $type
	 */
	public function doFoo(string $type): void
	{
	}

}

final class IsFinal
{

	public const FOO = 'foo';

	/**
	 * @param static::NOPE_* $type
	 */
	public function doFoo(string $type): void
	{
	}

	/**
	 * @param static::F* $type
	 */
	public function doBar(string $type): void
	{
	}

}

/**
 * @template T of NotFinal
 * @param T::NOPE_* $type
 */
function notFinalBound(string $type): void
{
}

/**
 * @template T of IsFinal
 * @param T::NOPE_* $type
 */
function finalBound(string $type): void
{
}
