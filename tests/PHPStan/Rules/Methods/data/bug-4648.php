<?php

namespace Bug4648;

interface ClassInterface
{
	/**
	 * @return static
	 */
	public static function convert();
}

trait ClassDefaultLogic
{
	/**
	 * @return static
	 */
	public static function convert()
	{
		return new self();
	}
}

final class ClassImplementation implements ClassInterface
{
	use ClassDefaultLogic;
}
