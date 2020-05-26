<?php

namespace TwoSame;

class Foo
{

	/** @var string */
	private $prop = 1;

}

if (rand(0, 0)) {
	class Foo
	{

		/** @var int */
		private $prop = 'str';

		/** @var int */
		private $prop2 = 'str';

	}
}
