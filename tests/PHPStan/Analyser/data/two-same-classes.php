<?php

namespace TwoSame;

class Foo
{

	/** @var string */
	private $prop = 1;

	public function doFoo(): void
	{
		echo self::FOO_CONST;
	}

}

if (rand(0, 1)) {
	class Foo
	{

		private const FOO_CONST = 'foo';

		/** @var int */
		private $prop = 'str';

		/** @var int */
		private $prop2 = 'str';

		public function doFoo(): void
		{
			echo self::FOO_CONST;
		}

	}
}
