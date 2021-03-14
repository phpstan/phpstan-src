<?php

namespace ThrowPoints\Helpers;

use Exception;

/**
 * @return mixed
 * @throws Exception
 */
function throws(...$args)
{
}

/**
 * @return mixed
 */
function maybeThrows(...$args)
{
}

/**
 * @return mixed
 * @throws void
 */
function doesntThrow(...$args)
{
}

class ThrowPointTestObject
{
	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function throws(...$args)
	{
	}

	/**
	 * @return mixed
	 */
	public function maybeThrows(...$args)
	{
	}

	/**
	 * @return mixed
	 * @throws void
	 */
	public function doesntThrow(...$args)
	{
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public static function staticThrows(...$args)
	{
	}

	/**
	 * @return mixed
	 */
	public static function staticMaybeThrows(...$args)
	{
	}

	/**
	 * @return mixed
	 * @throws void
	 */
	public static function staticDoesntThrow(...$args)
	{
	}
}
